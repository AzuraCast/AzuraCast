<?php
namespace DF\Form\Element;

class Radio extends \Zend_Form_Element_Radio
{
    public function init()
    {
        $this->setSeparator(' ');

        parent::init();
    }
}