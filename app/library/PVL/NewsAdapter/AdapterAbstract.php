<?php
namespace PVL\NewsAdapter;

class AdapterAbstract
{
    public static function filterSmartQuotes($text)
    {
        $text = str_replace(array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),  array("'", "'", '"', '"', '-', '--', '...'), $text);
        $text = str_replace(array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)), array("'", "'", '"', '"', '-', '--', '...'), $text);

        return $text;
    }

    public static function getDi()
    {
        return \Phalcon\Di::getDefault();
    }
}