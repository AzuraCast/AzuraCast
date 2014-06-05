<?php
/**
 * Returns a given variable if non-empty, otherwise returns a default value.
 */

namespace DF\View\Helper;
class Ifset extends \Zend_View_Helper_Abstract
{
    public function ifset($original_var, $default_value = "")
    {
        return ($original_var) ? $original_var : $default_value;
    }
}