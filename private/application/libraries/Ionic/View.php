<?php
namespace Ionic;

use ArrayAccess;
use Laravel\Messages;
use Session;

/**
 * Twig view
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class View implements ArrayAccess {

    /**
     * The name of the view.
     *
     * @var string
     */
    public $view;

    /**
     * The view data.
     *
     * @var array
     */
    public $data;

    /**
     * The path to the view on disk.
     *
     * @var string
     */
    public $path;

    /**
     * All of the shared view data.
     *
     * @var array
     */
    public static $shared = array();

    /**
     * Create a new view instance.
     *
     * <code>
     * 		// Create a new view instance
     * 		$view = new View('home.index');
     *
     * 		// Create a new view instance of a bundle's view
     * 		$view = new View('admin::home.index');
     *
     * 		// Create a new view instance with bound data
     * 		$view = new View('home.index', array('name' => 'Taylor'));
     * </code>
     *
     * @param  string  $view
     * @param  array   $data
     * @return void
     */
    public function __construct($view, $data = array())
    {
        $this->view = $view;
        $this->data = $data;

        $this->path = str_replace('.', DS, $view).'.twig';

        // If a session driver has been specified, we will bind an instance of the
        // validation error message container to every view. If an error instance
        // exists in the session, we will use that instance.
        if (!isset($this->data['errors']))
        {
            if (Session::started() and Session::has('errors'))
            {
                $this->data['errors'] = Session::get('errors');
            }
            else
            {
                $this->data['errors'] = new Laravel\Messages;
            }
        }
    }

    /**
     * Get the array of view data for the view instance.
     *
     * The shared view data will be combined with the view data.
     *
     * @return array
     */
    public function data()
    {
        $data = array_merge($this->data, static::$shared);

        // All nested views and responses are evaluated before the main view.
        // This allows the assets used by nested views to be added to the
        // asset container before the main view is evaluated.
        foreach ($data as $key => $value)
        {
            if ($value instanceof View or $value instanceof Response)
            {
                $data[$key] = $value->render();
            }
        }

        return $data;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @return string
     */
    public function get()
    {
        try {
            return \IoC::resolve('twig')->render($this->path, $this->data());
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Create a new view instance.
     *
     * <code>
     *		// Create a new view instance
     *		$view = View::make('home.index');
     *
     *		// Create a new view instance of a bundle's view
     *		$view = View::make('admin::home.index');
     *
     *		// Create a new view instance with bound data
     *		$view = View::make('home.index', array('name' => 'Taylor'));
     * </code>
     *
     * @param  string  $view
     * @param  array   $data
     * @return View
     */
    public static function make($view, $data = array())
    {
        return new static($view, $data);
    }

    /**
     * Add a view instance to the view data.
     *
     * <code>
     *		// Add a view instance to a view's data
     *		$view = View::make('foo')->nest('footer', 'partials.footer');
     *
     *		// Equivalent functionality using the "with" method
     *		$view = View::make('foo')->with('footer', View::make('partials.footer'));
     * </code>
     *
     * @param  string  $key
     * @param  string  $view
     * @param  array   $data
     * @return View
     */
    public function nest($key, $view, $data = array())
    {
        return $this->with($key, static::make($view, $data));
    }

    /**
     * Get the evaluated string content of the view.
     *
     * @return string
     */
    public function render()
    {
        return $this->get();
    }

    /**
     * Add a key / value pair to the shared view data.
     *
     * Shared view data is accessible to every view created by the application.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return View
     */
    public function shares($key, $value)
    {
        static::share($key, $value);
        return $this;
    }

    /**
     * Add a key / value pair to the shared view data.
     *
     * Shared view data is accessible to every view created by the application.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public static function share($key, $value)
    {
        static::$shared[$key] = $value;
    }

    /**
     * Simple PHP templates support
     *
     * @param   string  $file
     * @param   array   $__data
     * @return  string
     */
    public static function simple($file, $__data = array())
    {
        // The contents of each view file is cached in an array for the
        // request since partial views may be rendered inside of for
        // loops which could incur performance penalties.
        $__contents = file_get_contents(path('app').'views'.DS.str_replace('.', DS, $file).'.php');

        ob_start() and extract($__data, EXTR_SKIP);

        // We'll include the view contents for parsing within a catcher
        // so we can avoid any WSOD errors. If an exception occurs we
        // will throw it out to the exception handler.
        try
        {
	        eval('?>'.$__contents);
        }

        // If we caught an exception, we'll silently flush the output
        // buffer so that no partially rendered views get thrown out
        // to the client and confuse the user with junk.
        catch (\Exception $e)
        {
	        ob_get_clean(); throw $e;
        }

        return ob_get_clean();
    }

    /**
     * Add a key / value pair to the view data.
     *
     * Bound data will be available to the view as variables.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return View
     */
    public function with($key, $value = null)
    {
        if (is_array($key))
        {
            $this->data = array_merge($this->data, $key);
        }
        else
        {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Implementation of the ArrayAccess offsetExists method.
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * Implementation of the ArrayAccess offsetGet method.
     */
    public function offsetGet($offset)
    {
        if (isset($this[$offset])) return $this->data[$offset];
    }

    /**
     * Implementation of the ArrayAccess offsetSet method.
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * Implementation of the ArrayAccess offsetUnset method.
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Magic Method for handling dynamic data access.
     */
    public function __get($key)
    {
        return $this->data[$key];
    }

    /**
     * Magic Method for handling the dynamic setting of data.
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Magic Method for checking dynamically-set data.
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Get the evaluated string content of the view.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Magic Method for handling dynamic functions.
     *
     * This method handles calls to dynamic with helpers.
     */
    public function __call($method, $parameters)
    {
        if (strpos($method, 'with_') === 0)
        {
            $key = substr($method, 5);
            return $this->with($key, $parameters[0]);
        }

        throw new \Exception("Method [$method] is not defined on the View class.");
    }

}
