<?php

namespace Baseapp\Library;

use Baseapp\Models\Users;
use Baseapp\Models\Tokens;

/**
 * Auth Library
 *
 * @package     base-app
 * @category    Library
 * @version     2.0
 */
class Auth
{

    private $_config = array();
    private static $_instance;
    private $_cookies;
    private $_session;

    /**
     * Singleton pattern
     *
     * @package     base-app
     * @version     2.0
     *
     * @return Auth instance
     */
    public static function instance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new Auth;
        }

        return self::$_instance;
    }

    /**
     * Private constructor - disallow to create a new object
     *
     * @package     base-app
     * @version     2.0
     */
    private function __construct()
    {
        // Overwrite _config from config.ini
        if ($_config = \Phalcon\DI::getDefault()->getShared('config')->auth) {
            foreach ($_config as $key => $value) {
                $this->_config[$key] = $value;
            }
        }

        $this->_cookies = \Phalcon\DI::getDefault()->getShared('cookies');
        $this->_session = \Phalcon\DI::getDefault()->getShared('session');
    }

    /**
     * Private clone - disallow to clone the object
     *
     * @package     base-app
     * @version     2.0
     */
    private function __clone()
    {

    }

    /**
     * Logs a user in, based on the authautologin cookie.
     *
     * @package     base-app
     * @version     2.0
     *
     * @return mixed
     */
    private function auto_login()
    {
        if ($this->_cookies->has('authautologin')) {
            $cookieToken = $this->_cookies->get('authautologin')->getValue();

            // Load the token
            $token = Tokens::findFirst(array('token=:token:', 'bind' => array(':token' => $cookieToken)));

            // If the token exists
            if ($token) {
                // Load the user and his roles
                $user = $token->getUser();
                $roles = $this->get_roles($user);

                // If user has login role and tokens match, perform a login
                if (isset($roles['login']) && $token->user_agent === sha1(\Phalcon\DI::getDefault()->getShared('request')->getUserAgent())) {
                    // Save the token to create a new unique token
                    $token->token = $this->create_token();
                    $token->save();

                    // Set the new token
                    $this->_cookies->set('authautologin', $token->token, $token->expires);

                    // Finish the login
                    $this->complete_login($user);

                    // Regenerate session_id
                    session_regenerate_id();

                    // Store user in session
                    $this->_session->set($this->_config['session_key'], $user);
                    // Store user's roles in session
                    if ($this->_config['session_roles']) {
                        $this->_session->set($this->_config['session_roles'], $roles);
                    }

                    // Automatic login was successful
                    return $user;
                }

                // Token is invalid
                $token->delete();
            } else {
                $this->_cookies->set('authautologin', "", time() - 3600);
                $this->_cookies->delete('authautologin');
            }
        }

        return false;
    }

    /**
     * Complete the login for a user by incrementing the logins and saving login timestamp
     *
     * @package     base-app
     * @version     2.0
     *
     * @param object $user user from the model
     *
     * @return void
     */
    private function complete_login(Users $user)
    {
        // Update the number of logins
        $user->logins = $user->logins + 1;

        // Set the last login date
        $user->last_login = time();

        // Save the user
        $user->update();
    }

    /**
     * Create auto login token.
     *
     * @package     base-app
     * @version     2.0
     *
     * @return  string
     */
    protected function create_token()
    {
        do {
            $token = sha1(uniqid(\Phalcon\Text::random(\Phalcon\Text::RANDOM_ALNUM, 32), true));
        } while (Tokens::findFirst(array('token=:token:', 'bind' => array(':token' => $token))));

        return $token;
    }

    /**
     * Gets the roles of user.
     *
     * @package     base-app
     * @version     2.0
     *
     * @param object $user user from the model
     *
     * @return array
     */
    public function get_roles($user)
    {
        $roles = array();

        if ($user instanceof Users) {
            // Find related records for a particular user
            foreach ($user->getRoles() as $roleuser) {
                // Get related role
                $role = $roleuser->getRole()->toArray();
                $roles [$role['name']] = $role['id'];
            }
        }

        return $roles;
    }

    /**
     * Gets the currently logged in user from the session.
     * Returns null if no user is currently logged in.
     *
     * @package     base-app
     * @version     2.0
     *
     * @return mixed
     */
    public function get_user()
    {
        $user = $this->_session->get($this->_config['session_key']);

        // Check for "remembered" login
        if (!$user) {
            $user = $this->auto_login();
        }

        return $user;
    }

    /**
     * Perform a hmac hash, using the configured method.
     *
     * @package     base-app
     * @version     2.0
     *
     * @param string $str string to hash
     * @return string
     */
    public function hash($str)
    {
        if (!$this->_config['hash_key']) {
            throw new \Phalcon\Exception('A valid hash key must be set in your auth config.');
        }

        return hash_hmac($this->_config['hash_method'], $str, $this->_config['hash_key']);
    }

    /**
     * Checks if a session is active.
     *
     * @package     base-app
     * @version     2.0
     *
     * @param mixed $role role name
     *
     * @return boolean
     */
    public function logged_in($role = null)
    {
        // Get the user from the session
        $user = $this->get_user();
        if (!$user) {
            return false;
        }

        // If user exists in session
        if ($user) {
            // If we don't have a roll no further checking is needed
            if (!$role) {
                return true;
            }

            // Check if user has the role
            if ($this->_config['session_roles'] && $this->_session->has($this->_config['session_roles'])) {
                // Check in session
                $roles = $this->_session->get($this->_config['session_roles']);
                $role = isset($roles[$role]) ? $roles[$role] : null;
            } else {
                // Check in db
                $role = $user->hasRole($role);
            }

            // Return true if user has role
            return $role ? true : false;
        }
    }

    /**
     * Attempt to log in a user by using an ORM object and plain-text password.
     *
     * @package     base-app
     * @version     2.0
     *
     * @param string $user user to log in
     * @param string $password password to check against
     * @param boolean $remember enable autologin
     * @return boolean
     */
    public function login($user, $password, $remember = false)
    {
        if (! $user instanceof Users) {
            $username = $user;

            // Username not specified
            if (!$username) {
                return null;
            }

            // Load the user
            $user = Users::findFirst(array('username=:username:', 'bind' => array(':username' => $username)));
        }

        if ($user) {
            $roles = $this->get_roles($user);

            // Create a hashed password
            if (is_string($password)) {
                $password = $this->hash($password);
            }

            // If user have login role and the passwords match, perform a login
            if (isset($roles['login']) && $user->password === $password) {
                if ($remember === true) {
                    // Create a new autologin token
                    $token = new Tokens();
                    $token->user_id = $user->id;
                    $token->user_agent = sha1(\Phalcon\DI::getDefault()->getShared('request')->getUserAgent());
                    $token->token = $this->create_token();
                    $token->created = time();
                    $token->expires = time() + $this->_config['lifetime'];

                    if ($token->create() === true) {
                        // Set the autologin cookie
                        $this->_cookies->set('authautologin', $token->token, time() + $this->_config['lifetime']);
                    }
                }

                // Finish the login
                $this->complete_login($user);

                // Regenerate session_id
                session_regenerate_id();

                // Store user in session
                $this->_session->set($this->_config['session_key'], $user);
                // Store user's roles in session
                if ($this->_config['session_roles']) {
                    $this->_session->set($this->_config['session_roles'], $roles);
                }

                return true;
            } else {
                // Login failed
                return false;
            }
        }
        // No user found
        return null;
    }

    /**
     * Log out a user by removing the related session variables
     * Remove any autologin cookies.
     *
     * @package     base-app
     * @version     2.0
     *
     * @param boolean $destroy completely destroy the session
     * @param boolean $logoutAll remove all tokens for user
     * @return boolean
     */
    public function logout($destroy = false, $logoutAll = false)
    {
        if ($this->_cookies->has('authautologin')) {
            $cookieToken = $this->_cookies->get('authautologin')->getValue();

            // Delete the autologin cookie to prevent re-login
            $this->_cookies->set('authautologin', "", time() - 3600);
            $this->_cookies->delete('authautologin');

            // Clear the autologin token from the database
            $token = Tokens::findFirst(array('token=:token:', 'bind' => array(':token' => $cookieToken)));

            if ($logoutAll) {
                // Delete all user tokens
                foreach (Tokens::find(array('user_id=:user_id:', 'bind' => array(':user_id' => $token->user_id))) as $_token) {
                    $_token->delete();
                }
            } else {
                if ($token) {
                    $token->delete();
                }
            }
        }

        // Destroy the session completely
        if ($destroy === true) {
            $this->_session->destroy();
        } else {
            // Remove the user from the session
            $this->_session->remove($this->_config['session_key']);
            // Remove user's roles from the session
            if ($this->_config['session_roles']) {
                $this->_session->remove($this->_config['session_roles']);
            }

            // Regenerate session_id
            session_regenerate_id();
        }

        // Double check
        return !$this->logged_in();
    }

    /**
     * Refresh user data stored in the session from the database.
     * Returns null if no user is currently logged in.
     *
     * @package     base-app
     * @version     2.0
     *
     * @return mixed
     */
    public function refresh_user()
    {
        $user = $this->_session->get($this->_config['session_key']);

        if (!$user) {
            return null;
        } else {
            // Get user's data from db
            $user = Users::findFirst($user->id);
            $roles = $this->get_roles($user);

            // Regenerate session_id
            session_regenerate_id();

            // Store user in session
            $this->_session->set($this->_config['session_key'], $user);
            // Store user's roles in session
            if ($this->_config['session_roles']) {
                $this->_session->set($this->_config['session_roles'], $roles);
            }

            return $user;
        }
    }

}
