<?php
namespace DF\View\Helper;
class DateOrDefault extends \Zend_View_Helper_Abstract
{
    public function dateOrDefault($timestamp, $date_format = 'm/d/Y', $no_date_text = '(No Date)')
    {
        if ($timestamp != 0)
            return date($date_format, $timestamp);
        else
            return $no_date_text;
    }
}