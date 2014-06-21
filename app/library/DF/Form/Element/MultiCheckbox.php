<?php
namespace DF\Form\Element;

class MultiCheckbox extends \Zend_Form_Element_MultiCheckbox
{
    public function init()
    {
        $this->setSeparator(' ');

        parent::init();
    }
}