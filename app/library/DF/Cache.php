<?php
/** 
 * A static interface to the Zend_Cache class.
 */

namespace DF;

class Cache
{
    /**
     * User Cache
     */
    
    // Load data from the cache.
    public static function load($id)
    {
        $cache = self::getCache();
        return $cache->load($id);
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
        return $cache->test($id);
    }
    
    // Save an item to the cache.
    public static function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        $cache = self::getCache();
        return $cache->save($data, $id, $tags, $specificLifetime);
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
        return $cache->remove($id);
    }
    
    // Clean the cache.
    public static function clean($mode = 'all', $tags = array())
    {
        if ($mode == 'all' && $tags)
            $mode = \Zend_Cache::CLEANING_MODE_MATCHING_TAG;
        
        $cache = self::getCache();
        return $cache->clean($mode, $tags);
    }
    
    // Get all cache keys.
    public static function getKeys()
    {
        $cache = self::getCache();
        return $cache->getIds();
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
            $frontend_name = 'Core';
            $frontend_options = array(
                'cache_id_prefix' => self::getSitePrefix().'_user_',
                'lifetime' => 3600,
                'automatic_serialization' => true
            );
            
            // Choose the most optimal caching mechanism available.
            list($backend_name, $backend_options) = self::getBackendCache();
            
            self::$_user_cache = \Zend_Cache::factory($frontend_name, $backend_name, $frontend_options, $backend_options);
        }
        
        return self::$_user_cache;
    }
    
    /**
     * Page Cache
     */
    
    public static function page()
    {
        $auth = \Zend_Registry::get('auth');
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
    
    public static function getBackendCache()
    {
        $cache_dir = DF_INCLUDE_CACHE;
        $backend_options = array();
        
        if (extension_loaded('wincache') && class_exists('Zend_Cache_Backend_WinCache'))
        {
            $backend_name = 'WinCache';
        }
        else if (extension_loaded('xcache'))
        {
            $backend_name = 'Xcache';
        }
        else if (extension_loaded('apc'))
        {
            $backend_name = 'Apc';
        }
        else
        {
            $backend_name = 'File';
            $backend_options = array(
                'cache_dir' => $cache_dir,
                'file_name_prefix' => 'df_cache',
                'hashed_directory_perm' => 0777,
                'cache_file_perm' => 0777,
            );
        }
        
        return array($backend_name, $backend_options);
    }
}