<?php

namespace Baseapp\Library;

/**
 * Payment Library
 *
 * @package     base-app
 * @category    Library
 * @version     2.0
 */
abstract class Payment
{

    protected $_config = array();
    public static $_instances = array();
    protected $_response = array();
    protected $_fields = array();
    protected $_required = array();

    /**
     * Singleton pattern
     *
     * @package     base-app
     * @version     2.0
     *
     * @return object adapter
     */
    public static function instance($adapter)
    {
        if (!isset(self::$_instances[$adapter])) {
            $class = __NAMESPACE__ . '\Payment\\' . ucfirst($adapter);
            self::$_instances[$adapter] = new $class();
        }

        return self::$_instances[$adapter];
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
        if ($config = \Phalcon\DI::getDefault()->getShared('config')->payment) {
            $this->_config = $config;
        }
    }

    /**
     * Get api url
     *
     * @package     base-app
     * @version     2.0
     *
     * @return string
     */
    protected abstract function apiURL();

    /**
     * Check the response
     *
     * @package     base-app
     * @version     2.0
     *
     * @return mixed
     */
    public abstract function check();

    /**
     * Get the response value(s)
     *
     * @package     base-app
     * @version     2.0
     *
     * @param string $field field name
     * @return mixed
     */
    public abstract function get($field = null);

    /**
     * Makes a POST request
     *
     * @package     base-app
     * @version     2.0
     *
     * @param array $fields fields to send
     * @return mixed
     */
    protected abstract function post(array $fields);

    /**
     * Process the payment
     *
     * @package     base-app
     * @version     2.0
     *
     * @param array $params patameters
     * @return mixed
     */
    public abstract function process(array $params);

    /**
     * Get return url
     *
     * @package     base-app
     * @version     2.0
     *
     * @return string
     */
    protected abstract function returnURL();

    /**
     * Get site URL with path
     *
     * @package     base-app
     * @version     2.0
     *
     * @param string $uri path
     * @return string
     */
    protected function siteURL($uri)
    {
        $url = \Phalcon\DI::getDefault()->getShared('url');
        return $url->getStatic($uri);
    }

}
