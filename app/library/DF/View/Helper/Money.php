<?php
namespace DF\View\Helper;
class Money extends \Zend_View_Helper_Abstract
{
    public function money($amount)
    {
        return \DF\Utilities::money_format($amount);    
    }
}