<?php
/**
 * Mailto address escaping.
 */

namespace DF\View\Helper;
class Mailto extends HelperAbstract
{
    public function mailto($address, $link_text = NULL)
    {
        $address = substr(chunk_split(bin2hex(" $address"), 2, ";&#x"), 3,-3);
        $link_text = (is_null($link_text)) ? $address : $link_text;
        
        return '<a href="mailto:'.$address.'">'.$link_text.'</a>';
    }
}

