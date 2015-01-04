<?php
namespace DF\View\Helper;
class FormMarkup extends HelperAbstract
{
    public function formMarkup($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, value, attribs, options, listsep, disable
        
        $markup = $attribs['markup'];
        unset($attribs['markup']);
        
        $attribs['class'] = 'df-form-markup-area '.$attribs['class'];
        
        $return = '<span';
        foreach($attribs as $attr_key => $attr_item)
            $return .= ' '.$attr_key.'="'.$attr_item.'"';
            
        $return .= '>'.$markup.'</span>';
        return $return;
    }
}