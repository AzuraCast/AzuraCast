<?
namespace DF\Session;

class Instance implements \ArrayAccess
{
    protected $_namespace;

    public function __construct($namespace = 'default')
    {
        \DF\Session::start();

        $this->_namespace = $namespace;

        if (!isset($_SESSION[$this->_namespace]))
            $_SESSION[$this->_namespace] = array();
    }

    /**
     * Magic Method __set
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $_SESSION[$this->_namespace][$name] = $value;
    }

    /**
     * Magic Method __get
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($_SESSION[$this->_namespace][$name]))
            return $_SESSION[$this->_namespace][$name];
    }

    /**
     * Magic Method __isset
     *
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($_SESSION[$this->_namespace][$name]);
    }

    /**
     * Magic Method __unset
     *
     * @param $name
     */
    public function __unset($name)
    {
        unset($_SESSION[$this->_namespace][$name]);
    }

    /**
     * ArrayAccess form of __isset
     *
     * @param mixed $name
     * @return bool
     */
    public function offsetExists($name)
    {
        return isset($_SESSION[$this->_namespace][$name]);
    }

    /**
     * ArrayAccess form of __get
     *
     * @param mixed $name
     * @return mixed|void
     */
    public function offsetGet($name)
    {
        if (isset($_SESSION[$this->_namespace][$name]))
            return $_SESSION[$this->_namespace][$name];
    }

    /**
     * ArrayAccess form of __set
     *
     * @param mixed $name
     * @param mixed $value
     */
    public function offsetSet($name, $value)
    {
        if (isset($_SESSION[$this->_namespace][$name]))
            return $_SESSION[$this->_namespace][$name];
    }

    /**
     * ArrayAccess form of __unset
     *
     * @param mixed $name
     */
    public function offsetUnset($name)
    {
        unset($_SESSION[$this->_namespace][$name]);
    }
}