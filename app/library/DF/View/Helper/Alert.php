<?php
namespace DF\View\Helper;
class Alert extends HelperAbstract
{
    public function alert($message, $level = \App\Flash::INFO)
    {
        \App\Flash::addMessage($message, $level);
    }
}