<?php
namespace DF\Filter;
class WebAddress implements \Zend_Filter_Interface
{
    public function filter($value)
    {
		$value = trim($value);
		if ($value && substr($value, 0, 4) != "http")
			$value = 'http://'.str_replace('http://', '', $value);
		
		return $value;
    }
}