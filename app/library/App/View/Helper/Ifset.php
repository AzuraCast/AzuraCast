<?php
/**
 * Returns a given variable if non-empty, otherwise returns a default value.
 */

namespace App\View\Helper;
class Ifset extends HelperAbstract
{
    public function ifset($original_var, $default_value = "")
    {
        return ($original_var) ? $original_var : $default_value;
    }
}