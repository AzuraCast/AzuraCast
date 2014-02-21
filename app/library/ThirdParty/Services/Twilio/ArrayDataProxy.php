<?php

/**
 * DataProxy implementation using an array for storage backend.
 *
 * @category Services
 * @package  Services_Twilio
 * @author   Neuman Vong <neuman@twilio.com>
 * @license  http://creativecommons.org/licenses/MIT/ MIT
 * @link     http://pear.php.net/package/Services_Twilio
 */
class Services_Twilio_ArrayDataProxy
    implements Services_Twilio_DataProxy
{
    protected $array;

    /**
     * Constructor.
     *
     * @param array $array Array representation
     */
    public function __construct($array)
    {
        $this->array = $array;
    }

    /**
     * Implementation of retrieveData.
     *
     * @param string $key    The index
     * @param array  $params Optional parameters
     *
     * @return object Object representation
     */
    function retrieveData($key, array $params = array())
    {
        return (object) $this->array;
    }

    /**
     * Implementation of createData.
     *
     * @param string $key    The index
     * @param array  $params Optional parameters
     *
     * @return object Object representation
     */
    function createData($key, array $params = array())
    {
        return (object) $this->array;
    }

    /**
     * Implementation of updateData.
     *
     * @param array $params Update parameters
     *
     * @return object Object representation
     */
    function updateData(array $params)
    {
        return $this->array;
    }

    /**
     * Implementation of magic method __get.
     *
     * @param string $prop The name of the property to get
     *
     * @return mixed The value of the property
     */
    function __get($prop)
    {
        return is_array($this->array)
            ? $this->array['prop']
            : $this->array->$prop;
    }
}
