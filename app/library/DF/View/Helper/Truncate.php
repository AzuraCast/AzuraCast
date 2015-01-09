<?php
namespace DF\View\Helper;
class Truncate extends HelperAbstract
{
    public function truncate($text, $length=80)
    {
        return \DF\Utilities::truncateText($text, $length);
    }
}