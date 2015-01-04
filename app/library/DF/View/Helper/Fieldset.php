<?php
namespace DF\View\Helper;

class Fieldset extends HelperAbstract
{
    public function fieldset($name, $content, $attribs = null)
    {
        $info = $this->_getInfo($name, $content, $attribs);
        extract($info);
        
        if (!$attribs['legend'])
            return $content;
        else
            return parent::fieldset($name, $content, $attribs);
    }
}
