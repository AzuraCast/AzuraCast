<?
namespace DF\Session;

class Instance implements \ArrayAccess
{
    protected $_namespace;
    protected $_data;

    public function __construct($namespace = 'default')
    {
        $this->_namespace = $namespace;

        // Lazy load session.
        if (\DF\Session::exists())
        {
            \DF\Session::start();
            $this->_data = $_SESSION[$this->_namespace];
        }
        else
        {
            $this->_data = array();
        }
    }

    /**
     * Magic Method __set
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->_data[$name] = $value;

        if (\DF\Session::isActive()) {
            \DF\Session::start();

            if (!isset($_SESSION[$this->_namespace]))
                $_SESSION[$this->_namespace] = array();

            $_SESSION[$this->_namespace][$name] = $value;
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

        if (\DF\Session::isActive()) {
            \DF\Session::start();

            if (!isset($_SESSION[$this->_namespace]))
                $_SESSION[$this->_namespace] = array();

            $_SESSION[$this->_namespace][$name] = $value;
        }
    }

    /**
     * Magic Method __get
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->_data[$name]))
            return $this->_data[$name];

        return null;
    }

    /**
     * ArrayAccess form of __get
     *
     * @param mixed $name
     * @return mixed|void
     */
    public function offsetGet($name)
    {
        if (isset($this->_data[$name]))
            return $this->_data[$name];

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
        return isset($this->_data[$name]);
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
        unset($this->_data[$name]);

        if (\DF\Session::isActive()) {
            \DF\Session::start();
            unset($_SESSION[$this->_namespace][$name]);
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

        if (\DF\Session::isActive()) {
            \DF\Session::start();
            unset($_SESSION[$this->_namespace][$name]);
        }
    }
}