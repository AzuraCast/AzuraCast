<?php
namespace App;

class Cache
{
    /** @var \Redis */
    protected $redis;

    /** @var int Default length of time to keep cached items. */
    protected $default_ttl = 3600;

    public function __construct(\Redis $redis, $default_ttl = null)
    {
        $this->redis = $redis;

        if ($default_ttl !== null) {
            $this->default_ttl = $default_ttl;
        }
    }

    /**
     * Properly close the connection.
     */
    public function __destruct()
    {
        if ($this->redis instanceof \Redis) {
            try {
                $this->redis->close();
            } catch (\RedisException $e) {
                /*
                 * \Redis::close will throw a \RedisException("Redis server went away") exception if
                 * we haven't previously been able to connect to Redis or the connection has severed.
                 */
            }
        }
    }

    /**
     * Attempt to load an item from cache, or return default value if not found.
     *
     * @param $id
     * @param null $default
     * @return mixed|null
     */
    public function load($id, $default = null)
    {
        $result = $this->redis->get($this->_filterId($id));

        if ($result === false) {
            return (is_callable($default)) ? $default() : $default;
        }

        return unserialize($result);
    }

    /**
     * Alias of the "load" function.
     *
     * @param $id
     * @param null $default
     * @return mixed|null
     */
    public function get($id, $default = null)
    {
        return $this->load($id, $default);
    }

    /**
     * Test whether an ID is present in the cache.
     *
     * @param $id
     * @return bool
     */
    public function test($id)
    {
        $result = $this->redis->get($this->_filterId($id));
        return ($result !== false);
    }

    /**
     * Save an item to the cache.
     *
     * @param $data
     * @param $id
     * @param int|null $ttl
     */
    public function save($data, $id, $ttl = null)
    {
        if ($ttl === null || !is_numeric($ttl)) {
            $ttl = $this->default_ttl;
        }

        if ($ttl < 0) {
            $ttl = 0.1;
        }

        $this->redis->setex($this->_filterId($id), $ttl, serialize($data));
    }

    /**
     * Alias for the "save" function.
     *
     * @param $data
     * @param $id
     * @param int|null $ttl
     */
    public function set($data, $id, $ttl = null)
    {
        $this->save($data, $id, $ttl);
    }

    /**
     * Combination of the "get" and "set" functions to return an existing cache
     * item or set it if one doesn't exist.
     *
     * @param $id
     * @param null $default
     * @param bool|false $ttl
     * @return mixed|null
     */
    public function getOrSet($id, $default = null, $ttl = false)
    {
        $result = $this->redis->get($this->_filterId($id));

        if ($result === false) {

            if ($ttl === null || !is_numeric($ttl)) {
                $ttl = $this->default_ttl;
            }

            if ($ttl < 0) {
                $ttl = 0.1;
            }

            $data = (is_callable($default)) ? $default() : $default;
            $this->redis->setex($this->_filterId($id), $ttl, serialize($data));

            return $data;

        }

        return unserialize($result);
    }

    /**
     * Delete an item from the cache.
     *
     * @param $id
     * @return void
     */
    public function remove($id)
    {
        $this->redis->delete($this->_filterId($id));
    }

    /**
     * Clean the cache of all items.
     *
     * @return bool
     */
    public function clean()
    {
        return true; // Not used with Redis
    }

    protected function _filterId($id)
    {
        return str_replace('/', ':', ltrim($id, '/'));
    }
}