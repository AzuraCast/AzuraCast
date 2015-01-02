<?php
namespace DF\View\Helper;

class FormRadio extends \Zend_View_Helper_FormRadio
{
    public function formRadio($name, $value = null, $attribs = null,
        $options = null, $listsep = "<br />\n")
    {
        $raw = parent::formRadio($name, $value, $attribs, $options, "<br />");
        $raw_items = explode("<br />", $raw);
        
        $ul_class = ($attribs['inline'] || $listsep == ' ') ? 'inputs-list inline' : 'inputs-list';
        return '<ul class="'.$ul_class.'"><li>'.implode('</li><li>', $raw_items).'</li></ul>';
    }
}
