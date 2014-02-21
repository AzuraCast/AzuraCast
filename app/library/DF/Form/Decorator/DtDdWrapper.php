<?php
namespace DF\Form\Decorator;
class DtDdWrapper extends \Zend_Form_Decorator_Abstract
{
    public function render($content)
    {
        $elementName = $this->getElement()->getName();
        
        $dtLabel = $this->getOption('dtLabel');        
        $dt = '<dt id="' . $elementName . '-label">' . $dtLabel . '</dt>';
        $dd = '<dd id="' . $elementName . '-element">' . $content . '</dd>';
        
        if( null !== $dtLabel )
			return $dt.$dd;
		else
			return $dd;
    }
}
