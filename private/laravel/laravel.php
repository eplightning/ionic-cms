<?php
namespace Laravel;

use Closure;

/*
|--------------------------------------------------------------------------
| Bootstrap The Framework Core
|--------------------------------------------------------------------------
|
| By including this file, the core of the framework will be setup which
| includes the class auto-loader, and the registration of any bundles.
| Basically, once this file has been included, the entire framework
| may be used by the developer.
|
*/

define('EXT', '.php');
define('CRLF', "\n");
define('DEFAULT_BUNDLE', 'application');
define('MB_STRING', (int) function_exists('mb_get_info'));
ob_start('mb_output_handler');

class IoC {

    /**
     * The registered dependencies.
     *
     * @var array
     */
    public static $registry = array();

    /**
     * The resolved singleton instances.
     *
     * @var array
     */
    public static $singletons = array();

    /**
     * Register an object and its resolver.
     *
     * @param  string   $name
     * @param  mixed    $resolver
     * @param  bool     $singleton
     * @return void
     */
    public static function register($name, $resolver = null, $singleton = false)
    {
        if ($resolver === null) $resolver = $name;

        static::$registry[$name] = compact('resolver', 'singleton');
    }

    /**
     * Determine if an object has been registered in the container.
     *
     * @param  string  $name
     * @return bool
     */
    public static function registered($name)
    {
        return array_key_exists($name, static::$registry);
    }

    /**
     * Register an object as a singleton.
     *
     * Singletons will only be instantiated the first time they are resolved.
     *
     * @param  string   $name
     * @param  Closure  $resolver
     * @return void
     */
    public static function singleton($name, $resolver = null)
    {
        static::register($name, $resolver, true);
    }

    /**
     * Register an existing instance as a singleton.
     *
     * <code>
     *        // Register an instance as a singleton in the container
     *        IoC::instance('mailer', new Mailer);
     * </code>
     *
     * @param  string  $name
     * @param  mixed   $instance
     * @return void
     */
    public static function instance($name, $instance)
    {
        static::$singletons[$name] = $instance;
    }

    /**
     * Resolve a given type to an instance.
     *
     * <code>
     *        // Get an instance of the "mailer" object registered in the container
     *        $mailer = IoC::resolve('mailer');
     *
     *        // Get an instance of the "mailer" object and pass parameters to the resolver
     *        $mailer = IoC::resolve('mailer', array('test'));
     * </code>
     *
     * @param  string  $type
     * @param  array   $parameters
     * @return mixed
     */
    public static function resolve($type, $parameters = array())
    {
        // If an instance of the type is currently being managed as a singleton, we will
        // just return the existing instance instead of instantiating a fresh instance
        // so the developer can keep re-using the exact same object instance from us.
        if (isset(static::$singletons[$type]))
        {
            return static::$singletons[$type];
        }

        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume the type is the concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        if ( ! isset(static::$registry[$type]))
        {
            $concrete = $type;
        }
        else
        {
            if (isset(static::$registry[$type]['resolver']))
            {
                $concrete = static::$registry[$type]['resolver'];
            }
            else
            {
                $concrete = $type;
            }
        }

        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the type, as well as resolve any of
        // its nested dependencies recursively until they are each resolved.
        if ($concrete == $type or $concrete instanceof Closure)
        {
            $object = static::build($concrete, $parameters);
        }
        else
        {
            $object = static::resolve($concrete);
        }

        // If the requested type is registered as a singleton, we want to cache off
        // the instance in memory so we can return it later without creating an
        // entirely new instances of the object on each subsequent request.
        if (isset(static::$registry[$type]['singleton']) && static::$registry[$type]['singleton'] === true)
        {
            static::$singletons[$type] = $object;
        }

        Event::fire('laravel.resolving', array($type, $object));

        return $object;
    }

    /**
     * Instantiate an instance of the given type.
     *
     * @param  string  $type
     * @param  array   $parameters
     * @return mixed
     */
    protected static function build($type, $parameters = array())
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the function, which allows functions to be
        // used as resolvers for more fine-tuned resolution of the objects.
        if ($type instanceof Closure)
        {
            return call_user_func_array($type, $parameters);
        }

        $reflector = new \ReflectionClass($type);

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface of an Abstract Class and there is
        // no binding registered for the abstraction so we need to bail out.
        if ( ! $reflector->isInstantiable())
        {
            throw new \Exception("Resolution target [$type] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // If there is no constructor, that means there are no dependencies and
        // we can just resolve an instance of the object right away without
        // resolving any other types or dependencies from the container.
        if (is_null($constructor))
        {
            return new $type;
        }

        $dependencies = static::dependencies($constructor->getParameters());

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param  array  $parameters
     * @return array
     */
    protected static function dependencies($parameters)
    {
        $dependencies = array();

        foreach ($parameters as $parameter)
        {
            $dependency = $parameter->getClass();

            // If the class is null, it means the dependency is a string or some other
            // primitive type, which we can not resolve since it is not a class and
            // we'll just bomb out with an error since we have nowhere to go.
            if (is_null($dependency))
            {
                throw new \Exception("Unresolvable dependency resolving [$parameter].");
            }

            $dependencies[] = static::resolve($dependency->name);
        }

        return (array) $dependencies;
    }

}

class Event {

    /**
     * All of the registered events.
     *
     * @var array
     */
    public static $events = array();

    /**
     * The queued events waiting for flushing.
     *
     * @var array
     */
    public static $queued = array();

    /**
     * All of the registered queue flusher callbacks.
     *
     * @var array
     */
    public static $flushers = array();

    /**
     * Determine if an event has any registered listeners.
     *
     * @param  string  $event
     * @return bool
     */
    public static function listeners($event)
    {
        return isset(static::$events[$event]);
    }

    /**
     * Register a callback for a given event.
     *
     * <code>
     *        // Register a callback for the "start" event
     *        Event::listen('start', function() {return 'Started!';});
     *
     *        // Register an object instance callback for the given event
     *        Event::listen('event', array($object, 'method'));
     * </code>
     *
     * @param  string  $event
     * @param  mixed   $callback
     * @return void
     */
    public static function listen($event, $callback)
    {
        static::$events[$event][] = $callback;
    }

    /**
     * Override all callbacks for a given event with a new callback.
     *
     * @param  string  $event
     * @param  mixed   $callback
     * @return void
     */
    public static function override($event, $callback)
    {
        static::clear($event);

        static::listen($event, $callback);
    }

    /**
     * Add an item to an event queue for processing.
     *
     * @param  string  $queue
     * @param  string  $key
     * @param  mixed   $data
     * @return void
     */
    public static function queue($queue, $key, $data = array())
    {
        static::$queued[$queue][$key] = $data;
    }

    /**
     * Register a queue flusher callback.
     *
     * @param  string  $queue
     * @param  mixed   $callback
     * @return void
     */
    public static function flusher($queue, $callback)
    {
        static::$flushers[$queue][] = $callback;
    }

    /**
     * Clear all event listeners for a given event.
     *
     * @param  string  $event
     * @return void
     */
    public static function clear($event)
    {
        unset(static::$events[$event]);
    }

    /**
     * Fire an event and return the first response.
     *
     * <code>
     *        // Fire the "start" event
     *        $response = Event::first('start');
     *
     *        // Fire the "start" event passing an array of parameters
     *        $response = Event::first('start', array('Laravel', 'Framework'));
     * </code>
     *
     * @param  string  $event
     * @param  array   $parameters
     * @return mixed
     */
    public static function first($event, $parameters = array())
    {
        return head(static::fire($event, $parameters));
    }

    /**
     * Fire an event and return the first response.
     *
     * Execution will be halted after the first valid response is found.
     *
     * @param  string  $event
     * @param  array   $parameters
     * @return mixed
     */
    public static function until($event, $parameters = array())
    {
        return static::fire($event, $parameters, true);
    }

    /**
     * Flush an event queue, firing the flusher for each payload.
     *
     * @param  string  $queue
     * @return void
     */
    public static function flush($queue)
    {
        foreach (static::$flushers[$queue] as $flusher)
        {
            // We will simply spin through each payload registered for the event and
            // fire the flusher, passing each payloads as we go. This allows all
            // the events on the queue to be processed by the flusher easily.
            if ( ! isset(static::$queued[$queue])) continue;

            foreach (static::$queued[$queue] as $key => $payload)
            {
                array_unshift($payload, $key);

                call_user_func_array($flusher, $payload);
            }
        }
    }

    /**
     * Fire an event so that all listeners are called.
     *
     * <code>
     *        // Fire the "start" event
     *        $responses = Event::fire('start');
     *
     *        // Fire the "start" event passing an array of parameters
     *        $responses = Event::fire('start', array('Laravel', 'Framework'));
     *
     *        // Fire multiple events with the same parameters
     *        $responses = Event::fire(array('start', 'loading'), $parameters);
     * </code>
     *
     * @param  string|array  $events
     * @param  array         $parameters
     * @param  bool          $halt
     * @return array
     */
    public static function fire($events, $parameters = array(), $halt = false)
    {
        $responses = array();

        $parameters = (array) $parameters;

        // If the event has listeners, we will simply iterate through them and call
        // each listener, passing in the parameters. We will add the responses to
        // an array of event responses and return the array.
        foreach ((array) $events as $event)
        {
            if (static::listeners($event))
            {
                foreach (static::$events[$event] as $callback)
                {
                    $response = call_user_func_array($callback, $parameters);

                    // If the event is set to halt, we will return the first response
                    // that is not null. This allows the developer to easily stack
                    // events but still get the first valid response.
                    if ($halt and ! is_null($response))
                    {
                        return $response;
                    }

                    // After the handler has been called, we'll add the response to
                    // an array of responses and return the array to the caller so
                    // all of the responses can be easily examined.
                    $responses[] = $response;
                }
            }
        }

        return $halt ? null : $responses;
    }

}

class Config {

    protected static $database = array();
    protected static $items = array();

    public static function file($name)
    {
        if (isset(static::$items[$name])) return static::$items[$name];

        if (is_file(path('app').'config'.DS.$name.'.php'))
        {
            static::$items[$name] = require path('app').'config'.DS.$name.'.php';
        }
        else
        {
            static::$items[$name] = array();
        }

        return static::$items[$name];
    }

    public static function has($item)
    {
        return !is_null(static::get($item));
    }

    public static function get($item, $default = null)
    {
        $item = explode('.', $item, 2);

        if (empty($item[1]))
        {
            if (isset(static::$database[$item[0]]))
            {
                return array_merge(static::file($item[0]), static::$database[$item[0]]);
            }

            return static::file($item[0]);
        }

        if (isset(static::$database[$item[0]][$item[1]]))
            return static::$database[$item[0]][$item[1]];

        if (!isset(static::$items[$item[0]]))
            static::file($item[0]);

        return isset(static::$items[$item[0]][$item[1]]) ? static::$items[$item[0]][$item[1]] : value($default);
    }

    public static function set($item, $value)
    {
        if (strpos($item, '.') !== FALSE)
        {
            $item = explode('.', $item, 2);
        }
        else
        {
            $item = array($item, null);
        }

        if (!$item[1])
        {
            static::$database[$item[0]] = (array) $value;
        }
        else
        {
            if (!isset(static::$database[$item[0]])) static::$database[$item[0]] = array();

            static::$database[$item[0]][$item[1]] = $value;
        }
    }

    public static function set_from_array(array $data)
    {
        static::$database = $data;
    }
}

require path('sys').'helpers'.EXT; // Different namespace, can't just copypaste here

class Autoloader {

    /**
     * The mappings from class names to file paths.
     *
     * @var array
     */
    public static $mappings = array();

    /**
     * The directories that use the PSR-0 naming convention.
     *
     * @var array
     */
    public static $directories = array();

    /**
     * The mappings for namespaces to directories.
     *
     * @var array
     */
    public static $namespaces = array();

    /**
     * The mappings for underscored libraries to directories.
     *
     * @var array
     */
    public static $underscored = array();

    /**
     * All of the class aliases registered with the auto-loader.
     *
     * @var array
     */
    public static $aliases = array();

    /**
     * Load the file corresponding to a given class.
     *
     * This method is registered in the bootstrap file as an SPL auto-loader.
     *
     * @param  string  $class
     * @return void
     */
    public static function load($class)
    {
        // First, we will check to see if the class has been aliased. If it has,
        // we will register the alias, which may cause the auto-loader to be
        // called again for the "real" class name to load its file.
        if (isset(static::$aliases[$class]))
        {
            return class_alias(static::$aliases[$class], $class);
        }

        // All classes in Laravel are statically mapped. There is no crazy search
        // routine that digs through directories. It's just a simple array of
        // class to file path maps for ultra-fast file loading.
        elseif (isset(static::$mappings[$class]))
        {
            require static::$mappings[$class];

            return;
        }

        // If the class namespace is mapped to a directory, we will load the
        // class using the PSR-0 standards from that directory accounting
        // for the root of the namespace by trimming it off.
        foreach (static::$namespaces as $namespace => $directory)
        {
            if (starts_with($class, $namespace))
            {
                return static::load_namespaced($class, $namespace, $directory);
            }
        }

        static::load_psr($class);
    }

    /**
     * Load a namespaced class from a given directory.
     *
     * @param  string  $class
     * @param  string  $namespace
     * @param  string  $directory
     * @return void
     */
    protected static function load_namespaced($class, $namespace, $directory)
    {
        return static::load_psr(substr($class, strlen($namespace)), $directory);
    }

    /**
     * Attempt to resolve a class using the PSR-0 standard.
     *
     * @param  string  $class
     * @param  string  $directory
     * @return void
     */
    protected static function load_psr($class, $directory = null)
    {
        // The PSR-0 standard indicates that class namespaces and underscores
        // should be used to indicate the directory tree in which the class
        // resides, so we'll convert them to slashes.
        $file = str_replace(array('\\', '_'), '/', $class);

        $directories = $directory ?: static::$directories;

        $lower = strtolower($file);

        // Once we have formatted the class name, we'll simply spin through
        // the registered PSR-0 directories and attempt to locate and load
        // the class file into the script.
        foreach ((array) $directories as $directory)
        {
            if (is_file($path = $directory.$lower.EXT))
            {
                return require $path;
            }
            elseif (is_file($path = $directory.$file.EXT))
            {
                return require $path;
            }
        }
    }

    /**
     * Register an array of class to path mappings.
     *
     * @param  array  $mappings
     * @return void
     */
    public static function map($mappings)
    {
        static::$mappings = array_merge(static::$mappings, $mappings);
    }

    /**
     * Register a class alias with the auto-loader.
     *
     * @param  string  $class
     * @param  string  $alias
     * @return void
     */
    public static function alias($class, $alias)
    {
        static::$aliases[$alias] = $class;
    }

    /**
     * Register directories to be searched as a PSR-0 library.
     *
     * @param  string|array  $directory
     * @return void
     */
    public static function directories($directory)
    {
        $directories = static::format($directory);

        static::$directories = array_unique(array_merge(static::$directories, $directories));
    }

    /**
     * Map namespaces to directories.
     *
     * @param  array   $mappings
     * @param  string  $append
     * @return void
     */
    public static function namespaces($mappings, $append = '\\')
    {
        $mappings = static::format_mappings($mappings, $append);

        static::$namespaces = array_merge($mappings, static::$namespaces);
    }

    /**
     * Register underscored "namespaces" to directory mappings.
     *
     * @param  array  $mappings
     * @return void
     */
    public static function underscored($mappings)
    {
        static::namespaces($mappings, '_');
    }

    /**
     * Format an array of namespace to directory mappings.
     *
     * @param  array   $mappings
     * @param  string  $append
     * @return array
     */
    protected static function format_mappings($mappings, $append)
    {
        foreach ($mappings as $namespace => $directory)
        {
            // When adding new namespaces to the mappings, we will unset the previously
            // mapped value if it existed. This allows previously registered spaces to
            // be mapped to new directories on the fly.
            $namespace = trim($namespace, $append).$append;

            unset(static::$namespaces[$namespace]);

            $namespaces[$namespace] = head(static::format($directory));
        }

        return $namespaces;
    }

    /**
     * Format an array of directories with the proper trailing slashes.
     *
     * @param  array  $directories
     * @return array
     */
    protected static function format($directories)
    {
        return array_map(function($directory)
        {
            return rtrim($directory, DS).DS;

        }, (array) $directories);
    }

}

spl_autoload_register(array('Laravel\\Autoloader', 'load'));

Autoloader::namespaces(array(
    'Laravel'                          => path('sys'),
    'Symfony\Component\Console'        => path('sys').'vendor/Symfony/Component/Console',
    'Symfony\Component\HttpFoundation' => path('sys').'vendor/Symfony/Component/HttpFoundation'
));

if (defined('HACK_BROKENFCGI'))
{
    $req = strstr($_SERVER['REQUEST_URI'], '?');

    if ($req)
    {
        $req = substr($req, 1);

        if (!empty($req))
        {
            $_GET = array();

            $str = explode('&', $req);

            foreach ($str as $s)
            {
                if (empty($s)) continue;

                $s = explode('=', $s, 2);

                if (!isset($s[1]))
                {
                    $s[1] = '1'; // ?
                }

                $_GET[$s[0]] = $s[1];
            }
        }
    }

    unset($req);
}

if (magic_quotes())
{
    $magics = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);

    foreach ($magics as &$magic)
    {
        $magic = array_strip_slashes($magic);
    }
}

Request::$foundation = \Symfony\Component\HttpFoundation\LaravelRequest::createFromGlobals();
Request::set_env('production');

if (defined('STDIN'))
{
    $console = CLI\Command::options($_SERVER['argv']);

    list($arguments, $options) = $console;

    $options = array_change_key_case($options, CASE_UPPER);

    $_SERVER['CLI'] = $options;
}

/*
|--------------------------------------------------------------------------
| Setup Error & Exception Handling
|--------------------------------------------------------------------------
|
| Next we'll register custom handlers for all errors and exceptions so we
| can display a clean error message for all errors, as well as do any
| custom error logging that may be setup by the developer.
|
*/

set_exception_handler(function($e)
{
    require_once path('sys').'error'.EXT;

    Error::exception($e);
});


set_error_handler(function($code, $error, $file, $line)
{
    require_once path('sys').'error'.EXT;

    Error::native($code, $error, $file, $line);
});


register_shutdown_function(function()
{
    require_once path('sys').'error'.EXT;

    Error::shutdown();
});

/*
|--------------------------------------------------------------------------
| Report All Errors
|--------------------------------------------------------------------------
|
| By setting error reporting to -1, we essentially force PHP to report
| every error, and this is guaranteed to show every error on future
| releases of PHP. This allows everything to be fixed early!
|
*/

error_reporting(-1);

/*
|--------------------------------------------------------------------------
| Start The Application Bundle
|--------------------------------------------------------------------------
|
| The application "bundle" is the default bundle for the installation and
| we'll fire it up first. In this bundle's bootstrap, more configuration
| will take place and the developer can hook into some of the core
| framework events such as the configuration loader.
|
*/

require path('app').'start'.EXT;
require path('app').'routes'.EXT;

/*
|--------------------------------------------------------------------------
| Register The Catch-All Route
|--------------------------------------------------------------------------
|
| This route will catch all requests that do not hit another route in
| the application, and will raise the 404 error event so the error
| can be handled by the developer in their 404 event listener.
|
*/

Routing\Router::register('*', '(:all)', function()
{
    return Event::first('404');
});

/*
|--------------------------------------------------------------------------
| Route The Incoming Request
|--------------------------------------------------------------------------
|
| Phew! We can finally route the request to the appropriate route and
| execute the route to get the response. This will give an instance
| of the Response object that we can send back to the browser
|
*/

Request::$route = Routing\Router::route(Request::method(), URI::current());

$response = Request::$route->call();

/*
|--------------------------------------------------------------------------
| "Render" The Response
|--------------------------------------------------------------------------
|
| The render method evaluates the content of the response and converts it
| to a string. This evaluates any views and sub-responses within the
| content and sets the raw string result as the new response.
|
*/

$response->render();

/*
|--------------------------------------------------------------------------
| Persist The Session To Storage
|--------------------------------------------------------------------------
|
| If a session driver has been configured, we will save the session to
| storage so it is available for the next request. This will also set
| the session cookie in the cookie jar to be sent to the user.
|
*/

if (Session::started())
{
    Session::save();
}

/*
|--------------------------------------------------------------------------
| Send The Response To The Browser
|--------------------------------------------------------------------------
|
| We'll send the response back to the browser here. This method will also
| send all of the response headers to the browser as well as the string
| content of the Response. This should make the view available to the
| browser and show something pretty to the user.
|
*/

$response->send();

/*
|--------------------------------------------------------------------------
| And We're Done!
|--------------------------------------------------------------------------
|
| Raise the "done" event so extra output can be attached to the response.
| This allows the adding of debug toolbars, etc. to the view, or may be
| used to do some kind of logging by the application.
|
*/

Event::fire('laravel.done', array($response));

/*
|--------------------------------------------------------------------------
| Finish the request for PHP-FastCGI
|--------------------------------------------------------------------------
|
| Stopping the PHP process for PHP-FastCGI users to speed up some
| PHP queries. Acceleration is possible when there are actions in the
| process of script execution that do not affect server response.
| For example, saving the session in memcached can occur after the page
| has been formed and passed to a web server.
*/

$response->foundation->finish();
