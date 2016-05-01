<?php
namespace App\View\Helper;

class DefaultValue extends HelperAbstract
{
    public function defaultValue($var, $default_val = '')
    {
        return ($var) ? $var : $default_val;
    }
}