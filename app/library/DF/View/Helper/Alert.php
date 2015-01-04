<?php
namespace DF\View\Helper;
class Alert extends HelperAbstract
{
    public function alert($message, $level = \DF\Flash::INFO)
    {
        \DF\Flash::addMessage($message, $level);
    }
}