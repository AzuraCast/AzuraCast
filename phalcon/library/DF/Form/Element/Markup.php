<?php
/**
 * UNIX Timestamp Form Element
 */

namespace DF\Form\Element;
class Markup extends \Zend_Form_Element
{
    public $helper = 'formMarkup';
    
    public function getValue()
    {
        return NULL;
    }
}