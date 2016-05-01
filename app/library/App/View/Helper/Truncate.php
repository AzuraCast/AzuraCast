<?php
namespace App\View\Helper;
class Truncate extends HelperAbstract
{
    public function truncate($text, $length=80)
    {
        return \App\Utilities::truncateText($text, $length);
    }
}