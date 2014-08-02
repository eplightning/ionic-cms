<?php namespace Laravel; use Closure;

class Cache {

	/**
	 * All of the active cache drivers.
	 *
	 * @var array
	 */
	public static $drivers = array();

	/**
	 * The third-party driver registrar.
	 *
	 * @var array
	 */
	public static $registrar = array();

	/**
	 * Get a cache driver instance.
	 *
	 * If no driver name is specified, the default will be returned.
	 *
	 * <code>
	 *		// Get the default cache driver instance
	 *		$driver = Cache::driver();
	 *
	 *		// Get a specific cache driver instance by name
	 *		$driver = Cache::driver('memcached');
	 * </code>
	 *
	 * @param  string        $driver
	 * @return Cache\Drivers\Driver
	 */
	public static function driver($driver = null)
	{
		if (is_null($driver)) $driver = Config::get('cache.driver');

		if ( ! isset(static::$drivers[$driver]))
		{
			static::$drivers[$driver] = static::factory($driver);
		}

		return static::$drivers[$driver];
	}

	/**
	 * Create a new cache driver instance.
	 *
	 * @param  string  $driver
	 * @return Cache\Drivers\Driver
	 */
	protected static function factory($driver)
	{
		if (isset(static::$registrar[$driver]))
		{
			$resolver = static::$registrar[$driver];

			return $resolver();
		}

		switch ($driver)
		{
			case 'apc':
				return new Cache\Drivers\APC(Config::get('cache.key'));

			case 'file':
				return new Cache\Drivers\File(path('storage').'cache'.DS);

			case 'memcached':
				return new Cache\Drivers\Memcached(Memcached::connection(), Config::get('cache.key'));

			case 'memory':
				return new Cache\Drivers\Memory;

			case 'redis':
				return new Cache\Drivers\Redis(Redis::db());

			case 'database':
				return new Cache\Drivers\Database(Config::get('cache.key'));

			default:
				throw new \Exception("Cache driver {$driver} is not supported.");
		}
	}

	/**
	 * Register a third-party cache driver.
	 *
	 * @param  string   $driver
	 * @param  Closure  $resolver
	 * @return void
	 */
	public static function extend($driver, Closure $resolver)
	{
		static::$registrar[$driver] = $resolver;
	}

	/**
	 * Magic Method for calling the methods on the default cache driver.
	 *
	 * <code>
	 *		// Call the "get" method on the default cache driver
	 *		$name = Cache::get('name');
	 *
	 *		// Call the "put" method on the default cache driver
	 *		Cache::put('name', 'Taylor', 15);
	 * </code>
	 */
	public static function __callStatic($method, $parameters)
	{
		return call_user_func_array(array(static::driver(), $method), $parameters);
	}

	/**
	 * Determine if an item exists in the cache.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public static function has($key)
	{
		return static::driver()->has($key);
	}

	/**
	 * Write an item to the cache for a given number of minutes.
	 *
	 * <code>
	 *		// Put an item in the cache for 15 minutes
	 *		Cache::put('name', 'Taylor', 15);
	 * </code>
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  int     $minutes
	 * @return void
	 */
	public static function put($key, $value, $minutes = 60)
	{
		static::driver()->put($key, $value, $minutes);
	}

	/**
	 * Delete an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public static function forget($key)
	{
		static::driver()->forget($key);
	}
	
	/**
	 * Get an item from the cache.
	 *
	 * <code>
	 *		// Get an item from the cache driver
	 *		$name = Cache::driver('name');
	 *
	 *		// Return a default value if the requested item isn't cached
	 *		$name = Cache::get('name', 'Taylor');
	 * </code>
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public static function get($key, $default = null)
	{
		return static::driver()->get($key, $default);
	}
}
