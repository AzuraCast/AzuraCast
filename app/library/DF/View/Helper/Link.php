<?php
/**
 * Easy linking helper (useful in e-mails that include full link URLs).
 */

namespace DF\View\Helper;
class Link extends \Zend_View_Helper_Abstract
{
    public function link($url, $text = NULL, $target = NULL)
    {
        if ($text === NULL)
            $text = $url;
        
        if ($target !== NULL)
            $target = 'target="'.$target.'"';
        
        return '<a href="'.$url.'" '.$target.'>'.$text.'</a>';
    }
}