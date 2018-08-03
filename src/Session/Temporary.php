<?php
namespace App\Session;

class Temporary implements NamespaceInterface
{
    /**
     * @var \App\Session
     */
    protected $_session;

    /**
     * @var string The current namespace name.
     */
    protected $_namespace;

    /**
     * @var array
     */
    protected $_data;

    public function __construct(\App\Session $session, $namespace = 'default')
    {
        $this->_session = $session;
        $this->_namespace = $namespace;
        $this->_data = [];
    }

    /**
     * Magic Method __set
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
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
    }

    /**
     * Magic Method __get
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * ArrayAccess form of __get
     *
     * @param mixed $name
     * @return mixed|null
     */
    public function offsetGet($name)
    {
        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        }

        return null;
    }

    /**
     * Magic Method __isset
     *
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    /**
     * ArrayAccess form of __isset
     *
     * @param mixed $name
     * @return bool
     */
    public function offsetExists($name)
    {
        return isset($this->_data[$name]);
    }

    /**
     * Magic Method __unset
     *
     * @param $name
     */
    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    /**
     * ArrayAccess form of __unset
     *
     * @param mixed $name
     */
    public function offsetUnset($name)
    {
        unset($this->_data[$name]);
    }
}