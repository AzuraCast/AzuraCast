<?php
/** 
 * A static interface to the Zend_Cache class.
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

        if ($cache->exists($id))
            return $cache->get($id);
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
        return $cache->exists($id);
    }
    
    // Save an item to the cache.
    public static function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        $cache = self::getCache();

        if ($specificLifetime === false)
            $specificLifetime = self::$cache_lifetime;

        $cache->save($id, $data, $specificLifetime);
    }

    // Alias for the "set" function.
    public static function set($data, $id, $tags = array(), $specificLifetime = false)
    {
        self::save($data, $id, $tags, $specificLifetime);
    }
    
    // Special callback function to get or save a new cache entry.
    public static function getOrSet($id, $default = NULL, $tags = array(), $specificLifetime = false)
    {
        $cache = self::getCache();

        if ($cache->exists($id))
        {
            return $cache->get($id);
        }
        else
        {
            $result = (is_callable($default)) ? $default() : $default;
            if ($result !== null)
                self::save($result, $id, $tags, $specificLifetime);

            return $result;
        }
    }
    
    // Delete an item from the cache.
    public static function remove($id)
    {
        $cache = self::getCache();
        return $cache->delete($id);
    }
    
    // Clean the cache.
    public static function clean($mode = 'all', $tags = array())
    {
        $cache = self::getCache();
        if ($mode == 'all')
            return $cache->flush();
    }
    
    // Get all cache keys.
    public static function getKeys()
    {
        $cache = self::getCache();
        return $cache->queryKeys(self::getSitePrefix('user'));
    }

    /**
     * @param string $cache_level
     * @return \Phalcon\Cache\BackendInterface
     */
    public static function getCache($cache_level = 'user')
    {
        if ($cache_level == 'user')
        {
            return self::getUserCache();
        }
        else
        {
            $frontend = new \Phalcon\Cache\Frontend\Data();
            return self::getBackendCache($frontend, $cache_level);
        }
    }

    /**
     * Get the static user cache.
     *
     * @return \Phalcon\Cache\BackendInterface
     */
    public static function getUserCache()
    {
        static $user_cache;

        if (!$user_cache)
        {
            $frontend = new \Phalcon\Cache\Frontend\Data();
            $user_cache = self::getBackendCache($frontend, 'user');
        }

        return $user_cache;
    }

    /**
     * Page Cache
    public static function page()
    {
        $di = \Phalcon\Di::getDefault();
        $auth = $di->get('auth');

        if (!$auth->isLoggedIn() && !\DF\Flash::hasMessages())
        {
            $page_cache = self::getPageCache();
            $page_cache->start();
        }
    }
    
    protected static $_page_cache;
    public static function getPageCache()
    {
        if (!is_object(self::$_page_cache))
        {
            $frontend_name = 'Page';
            $frontend_options = array(
                'cache_id_prefix' => self::getSitePrefix().'_page_',
                'lifetime' => 3600,
                'automatic_serialization' => true,
                'default_options' => array(
                    'cache_with_session_variables' => TRUE,
                    'cache_with_cookie_variables' => TRUE,
                    'make_id_with_session_variables' => FALSE,
                    'make_id_with_cookie_variables' => FALSE,
                ),
            );
            
            // Choose the most optimal caching mechanism available.
            list($backend_name, $backend_options) = self::getBackendCache();
            
            self::$_page_cache = \Zend_Cache::factory($frontend_name, $backend_name, $frontend_options, $backend_options);
        }
        
        return self::$_page_cache;
    }
     */

    /**
     * @param \Phalcon\Cache\FrontendInterface $frontCache
     * @param string $cache_level
     * @return \Phalcon\Cache\BackendInterface
     */
    public static function getBackendCache(\Phalcon\Cache\FrontendInterface $frontCache, $cache_level = 'user')
    {
        $di = \Phalcon\Di::getDefault();
        $config = $di->get('config');

        $cache_config = $config->cache->toArray();

        switch($cache_config['cache'])
        {
            case 'redis':
                $redis_config = (array)$cache_config['redis'];
                $redis_config['prefix'] = self::getSitePrefix($cache_level, ':');

                return new \Phalcon\Cache\Backend\Redis($frontCache, $redis_config);
                break;

            case 'memcached':
                $memcached_config = (array)$cache_config['memcached'];
                $memcached_config['client'][\Memcached::OPT_PREFIX_KEY] = self::getSitePrefix($cache_level, '.');

                return new \Phalcon\Cache\Backend\Libmemcached($frontCache, $memcached_config);
                break;

            case 'file':
                $file_config = (array)$cache_config['file'];
                $file_config['prefix'] = self::getSitePrefix($cache_level, '_');

                return new \Phalcon\Cache\Backend\File($frontCache, $file_config);
                break;

            default:
            case 'memory':
            case 'ephemeral':
                return new \Phalcon\Cache\Backend\Memory($frontCache);
                break;
        }
    }

    /**
     * @param string $cache_level
     * @param string $cache_separator
     * @return string Compiled site prefix for cache use.
     */
    public static function getSitePrefix($cache_level = 'user', $cache_separator = '.')
    {
        static $cache_base;

        if (!$cache_base)
        {
            $folders = explode(DIRECTORY_SEPARATOR, DF_INCLUDE_ROOT);
            $base_folder = @array_pop($folders);

            if (strpos($base_folder, '.') !== FALSE)
                $base_folder = substr($base_folder, 0, strpos($base_folder, '.'));

            $cache_base = ($base_folder) ? preg_replace("/[^a-zA-Z0-9]/", "", $base_folder) : 'default';
        }

        return $cache_base.$cache_separator.$cache_level.$cache_separator;
    }
}