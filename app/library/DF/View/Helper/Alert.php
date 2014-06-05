<?php
namespace DF\View\Helper;
class Alert extends \Zend_View_Helper_Abstract
{
    public function alert($message, $level = \DF\Flash::INFO)
    {
        \DF\Flash::addMessage($message, $level);
    }
}