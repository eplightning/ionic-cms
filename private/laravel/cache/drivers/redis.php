<?php namespace Laravel\Cache\Drivers;

class Redis extends Driver {

    /**
     * The Redis database instance.
     *
     * @var \Laravel\Redis
     */
    protected $redis;

    /**
     * Create a new Redis cache driver instance.
     *
     * @param  \Laravel\Redis  $redis
     * @return void
     */
    public function __construct(\Laravel\Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return $this->redis->exists($key) != '0';
    }

    /**
     * Retrieve an item from the cache driver.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function retrieve($key)
    {
        if ( ! is_null($cache = $this->redis->get($key)))
        {
            return unserialize($cache);
        }
    }

    /**
     * Write an item to the cache for a given number of minutes.
     *
     * <code>
     *        // Put an item in the cache for 15 minutes
     *        Cache::put('name', 'Taylor', 15);
     * </code>
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  int     $minutes
     * @return void
     */
    public function put($key, $value, $minutes = 60)
    {
        $this->forever($key, $value);

        $this->redis->expire($key, $minutes * 60);
    }

    /**
     * Write an item to the cache that lasts forever.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        $this->redis->set($key, serialize($value));
    }

    /**
     * Delete an item from the cache.
     *
     * @param  string  $key
     * @return void
     */
    public function forget($key)
    {
        $this->redis->del($key);
    }

    /**
     * Return list of all cache keys
     *
     * @return array
     */
    public function list_all()
    {
        $names = $this->redis->keys('*');
        $list = array();

        if (!is_array($names))
            return array();

        foreach ($names as $key) {
            $ttl = (int) $this->redis->ttl($key);

            if ($ttl <= -2)
                continue;

            $list[] = array(
                'cache_name' => $key,
                'expiration' => $ttl == -1 ? 0 : time() + $ttl
            );
        }

        return $list;
    }

    /**
     * Forget multiple keys using wildcard
     *
     * @param $key
     */
    public function forget_multiple($key)
    {
        $results = $this->redis->keys($key);

        if (is_array($results) and !empty($results)) {
            $this->redis->del($results);
        }
    }

}