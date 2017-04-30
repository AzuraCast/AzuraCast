<?php
namespace App;

class Cache
{
    /**
     * @var \Stash\Pool
     */
    protected $_cache;

    /**
     * @var int Default length of time to keep cached items.
     */
    protected $_cache_lifetime = 3600;

    public function __construct(\Stash\Interfaces\DriverInterface $cache_driver, $cache_level = 'user')
    {
        $pool = new \Stash\Pool($cache_driver);
        $pool->setNamespace(self::getSitePrefix($cache_level));

        $this->_cache = $pool;
    }

    /**
     * Return the raw cache itself for manipulation.
     *
     * @return \Stash\Pool
     */
    public function getRawCache()
    {
        return $this->_cache;
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
        $item = $this->_cache->getItem($id);

        if ($item->isHit()) {
            return $item->get();
        } elseif (is_callable($default)) {
            return $default();
        } else {
            return $default;
        }
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
        $item = $this->_cache->getItem($id);

        return $item->isHit();
    }

    /**
     * Save an item to the cache.
     *
     * @param $data
     * @param $id
     * @param bool|false $specificLifetime
     */
    public function save($data, $id, $specificLifetime = false)
    {
        if ($specificLifetime === false) {
            $specificLifetime = $this->_cache_lifetime;
        }

        $item = $this->_cache->getItem($id);

        $item->lock();
        $item->set($data);

        $this->_cache->save($item);
    }

    /**
     * Alias for the "save" function.
     *
     * @param $data
     * @param $id
     * @param bool|false $specificLifetime
     */
    public function set($data, $id, $specificLifetime = false)
    {
        $this->save($data, $id, $specificLifetime);
    }

    /**
     * Combination of the "get" and "set" functions to return an existing cache
     * item or set it if one doesn't exist.
     *
     * @param $id
     * @param null $default
     * @param bool|false $specificLifetime
     * @return mixed|null
     */
    public function getOrSet($id, $default = null, $specificLifetime = false)
    {
        if ($specificLifetime === false) {
            $specificLifetime = $this->_cache_lifetime;
        }

        $item = $this->_cache->getItem($id);

        if (!$item->isMiss()) {
            return $item->get();
        } else {
            $item->lock();

            $result = (is_callable($default)) ? $default() : $default;
            if ($result !== null) {
                $item->set($result);
                $item->expiresAfter($specificLifetime);
            }

            $this->_cache->save($item);

            return $result;
        }
    }

    /**
     * Delete an item from the cache.
     *
     * @param $id
     * @return bool
     */
    public function remove($id)
    {
        $item = $this->_cache->getItem($id);

        return $item->clear();
    }

    /**
     * Clean the cache of all items.
     *
     * @return bool
     */
    public function clean()
    {
        return $this->_cache->clear();
    }

    /**
     * @param string $cache_level
     * @param string $cache_separator
     * @return string Compiled site prefix for cache use.
     */
    public static function getSitePrefix($cache_level = 'user', $cache_separator = '')
    {
        static $cache_base;

        if (!$cache_base) {
            $dir_hash = md5(APP_INCLUDE_ROOT);
            $cache_base = substr($dir_hash, 0, 3);
        }

        // Shortening of cache level names.
        if ($cache_level == 'user') {
            $cache_level = 'u';
        } elseif ($cache_level == 'doctrine') {
            $cache_level = 'db';
        } elseif ($cache_level == 'session') {
            $cache_level = 's';
        }

        return $cache_base . $cache_separator . $cache_level . $cache_separator;
    }
}