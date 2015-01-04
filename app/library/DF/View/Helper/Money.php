<?php
namespace DF\View\Helper;
class Money extends HelperAbstract
{
    public function money($amount)
    {
        return \DF\Utilities::money_format($amount);    
    }
}