<?php
/**
 * DF_Session:
 * Extender for session management
 */

namespace DF;
class Session
{
    public static function get($namespace = 'default')
    {
        return self::getNamespace($namespace);
    }
    
    public static function getNamespace($namespace = 'default')
    {
        static $sessions;
        
        if ($sessions === NULL)
            $sessions = array();
        
        $session_name = self::getNamespaceName($namespace);
    
        if (!isset($sessions[$session_name]))
            $sessions[$session_name] = new \Zend_Session_Namespace($session_name);
        
        return $sessions[$session_name];
    }
    
    public static function getNamespaceName($suffix = 'default')
    {
        $app_hash = strtoupper(substr(md5(DF_INCLUDE_BASE), 0, 5));
        return 'DF_'.$app_hash.'_'.$suffix;
    }
    
    public static function suspend()
    {
        @session_write_close();
    }
    public static function resume()
    {
        @session_start();
    }
}