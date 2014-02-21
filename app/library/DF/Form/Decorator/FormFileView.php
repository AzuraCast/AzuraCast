<?php
namespace DF\Form\Decorator;
class FormFileView extends \Zend_Form_Decorator_Abstract implements \Zend_Form_Decorator_Marker_File_Interface
{
    public function render($content)
    {
		return $content;
    }
}
