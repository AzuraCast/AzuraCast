<?php

/**
 * Abstraction of an instance resource from the Twilio API.
 *
 * @category Services
 * @package  Services_Twilio
 * @author   Neuman Vong <neuman@twilio.com>
 * @license  http://creativecommons.org/licenses/MIT/ MIT
 * @link     http://pear.php.net/package/Services_Twilio
 */ 
abstract class Services_Twilio_InstanceResource
    extends Services_Twilio_Resource
{
    /**
     * @param mixed $params An array of updates, or a property name
     * @param mixed $value  A value with which to update the resource
     *
     * @return null
     */
    public function update($params, $value = null)
    {
        if (!is_array($params)) {
            $params = array($params => $value);
        }
        $this->proxy->updateData($params);
    }

    /**
     * Set this resource's proxy.
     *
     * @param Services_Twilio_DataProxy $proxy An instance of DataProxy
     *
     * @return null
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * Get the value of a property on this resource.
     *
     * @param string $key The property name
     *
     * @return mixed Could be anything.
     */
    public function __get($key)
    {
        if ($subresource = $this->getSubresources($key)) {
            return $subresource;
        }
        return $this->proxy->$key;
    }
}
