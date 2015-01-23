<?php
/**
 * DF_Session:
 * Extender for session management
 */

namespace DF;
class Session
{
    protected static $prevent_sessions = false;

    public static function start()
    {
        static $is_started = false;

        if ($is_started)
            return true;

        if (DF_IS_COMMAND_LINE)
            return false;

        if (self::$prevent_sessions)
            return false;

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

    public static function isStarted()
    {
        if (defined('PHP_SESSION_ACTIVE'))
            return (session_status() !== PHP_SESSION_ACTIVE);
        else
            return (!session_id());
    }

    /**
     * Prevent sessions from being created.
     */
    public static function disable()
    {
        self::$prevent_sessions = true;
    }

    /**
     * Reallow sessions to be created after previously prevented.
     */
    public static function enable()
    {
        self::$prevent_sessions = false;
    }

}