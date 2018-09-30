<?php
namespace App;

class Session
{
    protected $_prevent_sessions = false;

    protected $_is_started = false;

    protected $_sessions = [];

    /** @var Session\Flash */
    protected $flash;

    /** @var Session\Csrf */
    protected $csrf;

    public function __construct()
    {
        $this->flash = new Session\Flash($this);
        $this->csrf = new Session\Csrf($this);

        // Disable sessions sending their own Cache-Control/Expires headers.
        session_cache_limiter('');

        // Set session-specific settings for PHP.
        ini_set('session.use_only_cookies',     1);
        ini_set('session.cookie_httponly',      1);
        ini_set('session.cookie_lifetime',      86400);
        ini_set('session.use_strict_mode',      1);
    }

    /**
     * Get the "Flash" alert helper.
     *
     * @return Session\Flash
     */
    public function getFlash(): Session\Flash
    {
        return $this->flash;
    }

    /**
     * Shortcut for $this->getFlash()->addMessage()
     *
     * @param $message
     * @param string $level
     * @param bool $save_in_session
     */
    public function flash($message, $level = Session\Flash::INFO, $save_in_session = true)
    {
        $this->flash->addMessage($message, $level, $save_in_session);
    }

    /**
     * Get the "CSRF" session helper.
     *
     * @return Session\Csrf
     */
    public function getCsrf(): Session\Csrf
    {
        return $this->csrf;
    }

    /**
     * Start the session handler if allowed and not already started.
     *
     * @return bool
     */
    public function start()
    {
        if ($this->_is_started) {
            return true;
        }

        if (!$this->isActive()) {
            return false;
        }

        $this->_is_started = @session_start();

        // Session regeneration detection code
        // from http://php.net/manual/en/function.session-regenerate-id.php
        if (isset($_SESSION['destroyed'])) {
            if (isset($_SESSION['new_session_id'])) {
                // Try again to set proper session ID cookie.
                session_write_close();
                session_id($_SESSION['new_session_id']);
                session_start();
            }
            if ($_SESSION['destroyed'] < time()-300) {
                $this->destroy();
            }
        }

        return $this->_is_started;
    }

    /**
     * Alias for self::getNamespace()
     *
     * @param string $namespace
     * @return Session\NamespaceInterface
     */
    public function get($namespace = 'default'): Session\NamespaceInterface
    {
        return $this->getNamespace($namespace);
    }

    /**
     * Get a session management namespace.
     *
     * @param string $namespace
     * @return Session\NamespaceInterface
     */
    public function getNamespace($namespace = 'default'): Session\NamespaceInterface
    {
        $session_name = $this->getNamespaceName($namespace);

        if (!isset($this->_sessions[$session_name])) {
            if (self::isActive()) {
                $session_instance = new Session\Instance($this, $session_name);
            } else {
                $session_instance = new Session\Temporary($this, $session_name);
            }

            $this->_sessions[$session_name] = $session_instance;
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

        return 'APP_' . $app_hash . '_' . $suffix;
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
        if (APP_IS_COMMAND_LINE && !APP_TESTING_MODE) {
            return false;
        }

        if ($this->_prevent_sessions) {
            return false;
        }

        return true;
    }

    /**
     * Regenerate a new session ID, while allowing smooth transition from older session ID for unstable connections.
     * Code pulled from http://php.net/manual/en/function.session-regenerate-id.php
     */
    public function regenerate()
    {
        // Handle lazy-loading of sessions
        if ($this->exists()) {
            $this->start();

            $new_session_id = session_create_id();

            // Set transition variables on old session.
            $existing_session = $_SESSION;
            $_SESSION['new_session_id'] = $new_session_id;
            $_SESSION['destroyed'] = time();

            // Write and close current session;
            session_write_close();

            // Start session with new session ID
            session_id($new_session_id);

            session_start();

            if (!empty($existing_session)) {
                $_SESSION = $existing_session;
            }
        }
    }

    /**
     * Destroy the current session.
     */
    public function destroy()
    {
        $this->start();

        // Unset all of the session variables.
        $_SESSION = [];

        // Destroy session cookie.
        if (ini_get("session.use_cookies")) {
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
        if (defined('PHP_SESSION_ACTIVE')) {
            return (session_status() !== PHP_SESSION_ACTIVE);
        } else {
            return (!session_id());
        }
    }
}
