<?php
namespace DF;

class Session
{
    protected static $prevent_sessions = false;

    /**
     * Start the session handler if allowed and not already started.
     *
     * @return bool
     */
    public static function start()
    {
        static $is_started = false;

        if ($is_started)
            return true;

        if (!self::isActive())
            return false;

        $is_started = session_start();
        return $is_started;
    }

    /**
     * Alias for self::getNamespace()
     *
     * @param string $namespace
     * @return mixed
     */
    public static function get($namespace = 'default')
    {
        return self::getNamespace($namespace);
    }

    /**
     * Get a session management namespace.
     *
     * @param string $namespace
     * @return mixed
     */
    public static function getNamespace($namespace = 'default')
    {
        static $sessions = array();

        $session_name = self::getNamespaceName($namespace);

        if (!isset($sessions[$session_name]))
        {
            if (self::isActive())
                $sessions[$session_name] = new \DF\Session\Instance($session_name);
            else
                $sessions[$session_name] = new \DF\Session\Temporary($session_name);
        }
        
        return $sessions[$session_name];
    }

    /**
     * Clean up the name of a session namespace for storage.
     *
     * @param string $suffix
     * @return string
     */
    public static function getNamespaceName($suffix = 'default')
    {
        $app_hash = strtoupper(substr(md5(DF_INCLUDE_BASE), 0, 5));
        return 'DF_'.$app_hash.'_'.$suffix;
    }

    /**
     * Temporarily suspend the session in mid-page.
     */
    public static function suspend()
    {
        @session_write_close();
    }

    /**
     * Resume a temporarily suspended session.
     */
    public static function resume()
    {
        @session_start();
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

    /**
     * Indicate if a session exists on the user's computer already.
     *
     * @return bool
     */
    public static function exists()
    {
        return isset($_COOKIE[session_name()]);
    }

    /**
     * Indicates if sessions are currently active (and permitted).
     *
     * @return bool
     */
    public static function isActive()
    {
        if (DF_IS_COMMAND_LINE)
            return false;

        if (self::$prevent_sessions)
            return false;

        return true;
    }

    /**
     * Indicates if a session has already been started in this page load.
     *
     * @return bool
     */
    public static function isStarted()
    {
        if (defined('PHP_SESSION_ACTIVE'))
            return (session_status() !== PHP_SESSION_ACTIVE);
        else
            return (!session_id());
    }
}