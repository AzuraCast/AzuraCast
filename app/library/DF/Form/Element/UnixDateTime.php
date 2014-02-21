<?php
/**
 * UNIX Timestamp Form Element
 */

namespace DF\Form\Element;
class UnixDateTime extends \Zend_Form_Element_Xhtml
{
    public $helper = 'formUnixDateTime';

    public $field_timestamp = 0;

    public $field_year;
    public $field_month;
    public $field_day;

    public $field_hour;
    public $field_minute;
    public $field_meridian;
    
    public function setValue($value)
    {
        if (is_numeric($value))
            $timestamp = $value;
        elseif (is_string($value))
            $timestamp = strtotime($value);
        elseif ($value instanceof \DateTime)
			$timestamp = $value->getTimestamp();
        elseif (is_array($value))
            $timestamp = self::processArray($value);
        else if (!$value)
			$timestamp = 0;
		else
            throw new \Exception('Invalid date value provided');
        
        $this->field_timestamp = (int)$timestamp;

        $this->field_year = ($timestamp) ? date('Y', $timestamp) : '';
        $this->field_month = ($timestamp) ? date('m', $timestamp) : '';
        $this->field_day = ($timestamp) ? date('d', $timestamp) : '';

        $this->field_hour = ($timestamp) ? date('g', $timestamp) : '';
        $this->field_minute = ($timestamp) ? date('i', $timestamp) : '';
        $this->field_meridian = ($timestamp) ? date('a', $timestamp) : '';
        
        return $this;
    }

    public function getValue()
    {
        return $this->field_timestamp;
    }
    
    public static function processArray($value, $default_timestamp = null)
    {
		$default_timestamp = $default_timestamp ?: time();
		
		$month = (isset($value['month'])) ? (int)$value['month'] : date('m', $default_timestamp);
		$day = (isset($value['day'])) ? (int)$value['day'] : date('d', $default_timestamp);
		$year = (isset($value['year'])) ? (int)$value['year'] : date('Y', $default_timestamp);
		
		$hour = (isset($value['hour'])) ? (int)$value['hour'] : date('g', $default_timestamp);
		$minute = (isset($value['minute'])) ? (int)$value['minute'] : date('i', $default_timestamp);
		$meridian = (isset($value['meridian'])) ? $value['meridian'] : date('a', $default_timestamp);
		
		return strtotime($month.'/'.$day.'/'.$year.' '.$hour.':'.str_pad($minute, 2, '0', STR_PAD_LEFT).' '.$meridian);
    }
}