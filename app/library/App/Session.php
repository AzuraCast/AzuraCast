<?php
namespace App;

class Session
{
    protected $_prevent_sessions = false;
    protected $_is_started = false;
    protected $_sessions = array();

    /**
     * Start the session handler if allowed and not already started.
     *
     * @return bool
     */
    public function start()
    {
        if ($this->_is_started)
            return true;

        if (!$this->isActive())
            return false;

        $this->_is_started = session_start();
        return $this->_is_started;
    }

    /**
     * Alias for self::getNamespace()
     *
     * @param string $namespace
     * @return mixed
     */
    public function get($namespace = 'default')
    {
        return $this->getNamespace($namespace);
    }

    /**
     * Get a session management namespace.
     *
     * @param string $namespace
     * @return mixed
     */
    public function getNamespace($namespace = 'default')
    {
        $session_name = self::getNamespaceName($namespace);

        if (!isset($this->_sessions[$session_name]))
        {
            if (self::isActive())
                $this->_sessions[$session_name] = new \App\Session\Instance($this, $session_name);
            else
                $this->_sessions[$session_name] = new \App\Session\Temporary($this, $session_name);
        }
        
        return $this->_sessions[$session_name];
    }

    /**
     * Clean up the name of a session namespace for storage.
     *
     * @param string $suffix
     * @return string
     */
    public function getNamespaceName($suffix = 'default')
    {
        $app_hash = strtoupper(substr(md5(APP_INCLUDE_BASE), 0, 5));
        return 'APP_'.$app_hash.'_'.$suffix;
    }

    /**
     * Temporarily suspend the session in mid-page.
     */
    public function suspend()
    {
        @session_write_close();
    }

    /**
     * Resume a temporarily suspended session.
     */
    public function resume()
    {
        @session_start();
    }

    /**
     * Prevent sessions from being created.
     */
    public function disable()
    {
        $this->_prevent_sessions = true;
    }

    /**
     * Reallow sessions to be created after previously prevented.
     */
    public function enable()
    {
        $this->_prevent_sessions = false;
    }

    /**
     * Indicate if a session exists on the user's computer already.
     *
     * @return bool
     */
    public function exists()
    {
        return isset($_COOKIE[session_name()]);
    }

    /**
     * Indicates if sessions are currently active (and permitted).
     *
     * @return bool
     */
    public function isActive()
    {
        if (APP_IS_COMMAND_LINE)
            return false;

        if ($this->_prevent_sessions)
            return false;

        return true;
    }

    /**
     * Destroy a session.
     */

    public function destroy()
    {
        $this->start();

        // Unset all of the session variables.
        $_SESSION = array();

        // Destroy session cookie.
        if (ini_get("session.use_cookies"))
        {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy session formally.
        session_destroy();
    }

    /**
     * Indicates if a session has already been started in this page load.
     *
     * @return bool
     */
    public function isStarted()
    {
        if (defined('PHP_SESSION_ACTIVE'))
            return (session_status() !== PHP_SESSION_ACTIVE);
        else
            return (!session_id());
    }
}