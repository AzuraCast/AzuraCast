<?php
/**
 * UNIX Timestamp Form Element
 */

namespace DF\Form\Element;
class UnixDate extends \Zend_Form_Element_Xhtml
{
    public $helper = 'formUnixDate';

    public $field_timestamp;
    public $field_year;
    public $field_month;
    public $field_day;

    public function setValue($value)
    {
        if (is_numeric($value))
            $timestamp = $value;
        elseif (is_string($value))
            $timestamp = strtotime($value.' UTC');
        elseif ($value instanceof \DateTime)
            $timestamp = $value->getTimestamp();
        elseif (is_array($value))
            $timestamp = self::processArray($value);
        else if (!$value)
            $timestamp = 0;
        else
            throw new \Exception('Invalid date value provided');
        
        $this->field_timestamp = (int)$timestamp;
        $this->field_year = ($timestamp) ? gmdate('Y', $timestamp) : '';
        $this->field_month = ($timestamp) ? gmdate('m', $timestamp) : '';
        $this->field_day = ($timestamp) ? gmdate('d', $timestamp) : '';
        
        return $this;
    }

    public function getValue()
    {
        return $this->field_timestamp;
    }
    
    public static function processArray($value, $default_timestamp = null)
    {
        $default_timestamp = $default_timestamp ?: time();
        
        if (empty($value['month']) && empty($value['day']) && empty($value['year']))
        {
            return $default_timestamp;
        }
        else
        {
            $month = (!empty($value['month'])) ? (int)$value['month'] : gmdate('m', $default_timestamp);
            $day = (!empty($value['day'])) ? (int)$value['day'] : gmdate('d', $default_timestamp);
            $year = (!empty($value['year'])) ? (int)$value['year'] : gmdate('Y', $default_timestamp);
            
            return strtotime($month.'/'.$day.'/'.$year.' 00:00:00 UTC');
        }
    }
}