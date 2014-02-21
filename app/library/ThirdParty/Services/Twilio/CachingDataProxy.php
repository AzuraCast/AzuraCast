<?php

/**
 * DataProxy implmentation that caches the output of the proxy it wraps.
 *
 * @category Services
 * @package  Services_Twilio
 * @author   Neuman Vong <neuman@twilio.com>
 * @license  http://creativecommons.org/licenses/MIT/ MIT
 * @link     http://pear.php.net/package/Services_Twilio
 */ 
class Services_Twilio_CachingDataProxy
    implements Services_Twilio_DataProxy
{
    /**
     * The proxy being wrapped.
     *
     * @var DataProxy $proxy
     */
    protected $proxy;

    /**
     * The principal data used to retrieve an object from the proxy.
     *
     * @var array $principal
     */
    protected $principal;

    /**
     * The object cache.
     *
     * @var object $cache
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param array                     $principal Usually the SID
     * @param Services_Twilio_DataProxy $proxy     The proxy
     * @param object|null               $cache     The cache
     */
    public function __construct($principal, Services_Twilio_DataProxy $proxy,
        $cache = null
    ) {
        if (is_scalar($principal)) {
            $principal = array('sid' => $principal, 'params' => array());
        }
        $this->principal = $principal;
        $this->proxy = $proxy;
        $this->cache = $cache;
    }

    /**
     * Set the object cache.
     *
     * @param object $object The new object
     *
     * @return null
     */
    public function setCache($object)
    {
        $this->cache = $object;
    }

    /**
     * Implementation of magic method __get.
     *
     * @param string $prop The name of the property to get
     *
     * @return mixed The value of the property
     */
    public function __get($prop)
    {
        if ($prop == 'sid') {
            return $this->principal['sid'];
        }
        if (empty($this->cache)) {
            $this->_load();
        }
        return isset($this->cache->$prop)
            ? $this->cache->$prop
            : null;
    }

    /**
     * Implementation of retrieveData.
     *
     * @param string $path   The path
     * @param array  $params Optional parameters
     *
     * @return object Object representation
     */
    public function retrieveData($path, array $params = array())
    {
        return $this->proxy->retrieveData(
            $this->principal['sid'] . "/$path",
            $params
        );
    }

    /**
     * Implementation of createData.
     *
     * @param string $path   The path
     * @param array  $params Optional parameters
     *
     * @return object Object representation
     */
    public function createData($path, array $params = array())
    {
        return $this->proxy->createData(
            $this->principal['sid'] . "/$path",
            $params
        );
    }

    /**
     * Implementation of updateData.
     *
     * @param array $params Update parameters
     *
     * @return object Object representation
     */
    public function updateData($params)
    {
        $this->cache = $this->proxy->createData(
            $this->principal['sid'],
            $params
        );
        return $this;
    }

    /**
     * Implementation of deleteData.
     *
     * @param string $path   The path
     * @param array  $params Optional parameters
     *
     * @return null
     */
    public function deleteData($path, array $params = array())
    {
        $this->proxy->delete(
            $this->principal['sid'] . "/$path",
            $params
        );
    }

    /**
     * Retrieves object from proxy into cache, then initializes subresources.
     *
     * @param object|null $object The object
     *
     * @return null
     */
    private function _load($object = null)
    {
        $this->cache = $object !== null
            ? $object
            : $this->proxy->retrieveData($this->principal['sid']);
    }
}
