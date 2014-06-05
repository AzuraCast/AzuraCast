<?php
namespace DF\View\Helper;
class Truncate extends \Zend_View_Helper_Abstract
{
    public function truncate($text, $length=80)
    {
        return \DF\Utilities::truncateText($text, $length);
    }
}