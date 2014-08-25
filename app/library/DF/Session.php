<?php
/**
 * DF_Session:
 * Extender for session management
 */

namespace DF;
class Session
{
    public static function start()
    {
        static $is_started = false;

        if (DF_IS_COMMAND_LINE)
            return false;

        if (!$is_started)
            $is_started = session_start();

        return $is_started;
    }

    public static function get($namespace = 'default')
    {
        return self::getNamespace($namespace);
    }
    
    public static function getNamespace($namespace = 'default')
    {
        if (!self::start())
            return new \stdClass;

        static $sessions = array();

        $session_name = self::getNamespaceName($namespace);

        if (!isset($sessions[$session_name]))
        {
            if (DF_IS_COMMAND_LINE)
                $sessions[$session_name] = new \stdClass;
            else
                $sessions[$session_name] = new \DF\Session\Instance($session_name);
        }
        
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