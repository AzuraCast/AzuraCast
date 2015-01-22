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
    public static function load($id)
    {
        $cache = self::getCache();
        return $cache->get($id);
    }

    // Alias of the "load" function.
    public static function get($id, $default = NULL)
    {
        return self::load($id);
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
        $result = self::load($id);
        
        if ($result === false)
        {
            $result = (is_callable($default)) ? $default() : $default;
            if ($result !== null)
            {
                self::save($result, $id, $tags, $specificLifetime);
            }
        }
        
        return $result;
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
        return $cache->queryKeys(self::getSitePrefix().'_user_');
    }
    
    // Retrieve or initialize the cache.
    protected static $_user_cache;
    
    public static function getCache()
    {
        return self::getUserCache();
    }
    public static function getUserCache()
    {
        if (!is_object(self::$_user_cache))
        {
            $frontend = new \Phalcon\Cache\Frontend\Data();

            self::$_user_cache = self::getBackendCache($frontend);
        }
        
        return self::$_user_cache;
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
     * Generic Cache Details
     */
    
    public static function getSitePrefix()
    {
        $folders = explode(DIRECTORY_SEPARATOR, DF_INCLUDE_ROOT);
        $base_folder = @array_pop($folders);
        
        if (strpos($base_folder, '.') !== FALSE)
            $base_folder = substr($base_folder, 0, strpos($base_folder, '.'));
        
        return ($base_folder) ? preg_replace("/[^a-zA-Z0-9]/", "", $base_folder) : 'default';
    }
    
    public static function getBackendCache(\Phalcon\Cache\FrontendInterface $frontCache)
    {
        $cache_dir = DF_INCLUDE_CACHE;
        $cache_prefix = self::getSitePrefix().'_user_';

        if (DF_APPLICATION_ENV == 'production') {
            return new \Phalcon\Cache\Backend\Libmemcached($frontCache, array(
                'servers' => array(
                    array('host' => 'localhost', 'port' => 11211, 'weight' => 1),
                ),
                'client' => array(
                    \Memcached::OPT_HASH => \Memcached::HASH_MD5,
                    \Memcached::OPT_PREFIX_KEY => 'prefix.',
                ),
            ));
        } else {
            return new \Phalcon\Cache\Backend\File($frontCache, array(
                'cacheDir' => $cache_dir.DIRECTORY_SEPARATOR,
                'prefix' => $cache_prefix,
            ));
        }

        /*
        if (extension_loaded('xcache'))
        {
            return new \Phalcon\Cache\Backend\Xcache($frontCache, array(
                'prefix' => $cache_prefix,
            ));
        }
        else if (extension_loaded('apc'))
        {
            return new \Phalcon\Cache\Backend\Apc($frontCache, array(
                'prefix' => $cache_prefix,
            ));
        }
        else
        {

        }
        */
    }
}