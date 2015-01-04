<?php
namespace DF\View\Helper;

class FormMultiCheckbox extends HelperAbstract
{
    public function formMultiCheckbox($name, $value = null, $attribs = null,
        $options = null, $listsep = "<br />\n")
    {
        $raw = parent::formRadio($name, $value, $attribs, $options);
        $raw_items = explode("<br />", $raw);
        
        $ul_class = ($attribs['inline']) ? 'inputs-list inline' : 'inputs-list';
        return '<ul class="'.$ul_class.'"><li>'.implode('</li><li>', $raw_items).'</li></ul>';
    }
}
