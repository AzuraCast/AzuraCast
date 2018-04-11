<?php
namespace App\Session;

class Instance extends Temporary
{
    public function __construct(\App\Session $session, $namespace = 'default')
    {
        parent::__construct($session, $namespace);

        // Lazy load session.
        if ($this->_session->exists()) {
            $this->_session->start();
            $this->_data = $_SESSION[$this->_namespace];
        }
    }

    /**
     * ArrayAccess form of __set
     *
     * @param mixed $name
     * @param mixed $value
     */
    public function offsetSet($name, $value)
    {
        $this->_data[$name] = $value;

        if ($this->_session->isActive()) {
            $this->_session->start();

            $_SESSION[$this->_namespace][$name] = $value;
        }
    }

    /**
     * ArrayAccess form of __unset
     *
     * @param mixed $name
     */
    public function offsetUnset($name)
    {
        unset($this->_data[$name]);

        if ($this->_session->isActive()) {
            $this->_session->start();

            unset($_SESSION[$this->_namespace][$name]);
            if (empty($_SESSION[$this->_namespace])) {
                unset($_SESSION[$this->_namespace]);
            }
        }
    }
}