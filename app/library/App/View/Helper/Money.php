<?php
namespace App\View\Helper;
class Money extends HelperAbstract
{
    public function money($amount)
    {
        return \App\Utilities::money_format($amount);
    }
}