<?php
namespace DF\View\Helper;
class DefaultValue extends \Zend_View_Helper_Abstract
{
    public function defaultValue($var, $default_val = '')
    {
        return ($var) ? $var : $default_val;
    }
}