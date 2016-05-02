<?php
/** 
 * A static interface for user cache management.
 */

namespace DF;

class Cache
{
    static $cache_lifetime = 3600;

    /**
     * User Cache
     */
    
    // Load data from the cache.
    public static function load($id, $default = NULL)
    {
        $cache = self::getCache();
        $item = $cache->getItem($id);

        if (!$item->isMiss())
            return $item->get();
        elseif (is_callable($default))
            return $default();
        else
            return $default;
    }

    // Alias of the "load" function.
    public static function get($id, $default = NULL)
    {
        return self::load($id, $default);
    }
    
    // Test whether an ID is present in the cache.
    public static function test($id)
    {
        $cache = self::getCache();
        $item = $cache->getItem($id);
        return !$item->isMiss();
    }
    
    // Save an item to the cache.
    public static function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        if ($specificLifetime === false)
            $specificLifetime = self::$cache_lifetime;

        $cache = self::getCache();
        $item = $cache->getItem($id);
        $item->set($data, $specificLifetime);
    }

    // Alias for the "set" function.
    public static function set($data, $id, $tags = array(), $specificLifetime = false)
    {
        self::save($data, $id, $tags, $specificLifetime);
    }
    
    // Special callback function to get or save a new cache entry.
    public static function getOrSet($id, $default = NULL, $tags = array(), $specificLifetime = false)
    {
        if ($specificLifetime === false)
            $specificLifetime = self::$cache_lifetime;

        $cache = self::getCache();
        $item = $cache->getItem($id);

        if (!$item->isMiss())
        {
            return $item->get();
        }
        else
        {
            $item->lock();

            $result = (is_callable($default)) ? $default() : $default;
            if ($result !== null)
                $item->set($result, $specificLifetime);

            return $result;
        }
    }
    
    // Delete an item from the cache.
    public static function remove($id)
    {
        $cache = self::getCache();

        $item = $cache->getItem($id);
        return $item->clear();
    }
    
    // Clean the cache.
    public static function clean($mode = 'all', $tags = array())
    {
        $cache = self::getCache();
        return $cache->flush();
    }
    
    // Get all cache keys.
    public static function getKeys()
    {
        throw new \App\Exception('Function not implemented.');
    }

    /**
     * @param string $cache_level
     * @return \Stash\Pool
     */
    public static function getCache($cache_level = 'user')
    {
        static $caches;
        static $cache_driver;

        if (!$caches)
            $caches = array();

        if (isset($caches[$cache_level]))
            return $caches[$cache_level];

        if (!$cache_driver)
            $cache_driver = self::getCacheDriver();

        $pool = new \Stash\Pool($cache_driver);
        $pool->setNamespace(self::getSitePrefix($cache_level));

        $caches[$cache_level] = $pool;
        return $pool;
    }

    /**
     * Return the application-configured driver.
     *
     * @return \Stash\Interfaces\DriverInterface
     */
    public static function getCacheDriver()
    {
        $di = \Phalcon\Di::getDefault();
        $config = $di->get('config');

        $cache_config = $config->cache->toArray();

        switch($cache_config['cache'])
        {
            case 'redis':
                $cache_driver = new \Stash\Driver\Redis();
                $cache_driver->setOptions($cache_config['redis']);
                return $cache_driver;
                break;

            case 'memcached':
                $cache_driver = new \Stash\Driver\Memcache();
                $cache_driver->setOptions($cache_config['memcached']);
                return $cache_driver;
                break;

            case 'file':
                $cache_driver = new \Stash\Driver\FileSystem();
                $cache_driver->setOptions($cache_config['file']);
                return $cache_driver;
                break;

            default:
            case 'memory':
            case 'ephemeral':
                return new \Stash\Driver\Ephemeral();
                break;
        }
    }

    /**
     * @param string $cache_level
     * @param string $cache_separator
     * @return string Compiled site prefix for cache use.
     */
    public static function getSitePrefix($cache_level = 'user', $cache_separator = '')
    {
        static $cache_base;

        if (!$cache_base)
        {
            $dir_hash = md5(APP_INCLUDE_ROOT);
            $cache_base = substr($dir_hash, 0, 3);
        }

        // Shortening of cache level names.
        if ($cache_level == 'user')
            $cache_level = 'u';
        elseif ($cache_level == 'doctrine')
            $cache_level = 'db';
        elseif ($cache_level == 'session')
            $cache_level = 's';

        return $cache_base.$cache_separator.$cache_level.$cache_separator;
    }
}