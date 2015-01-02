<?php
namespace DF\View\Helper;
class SpanFormErrors extends \Zend_View_Helper_FormErrors
{
    public function SpanFormErrors($errors, array $options = null)
    {
        $this->setElementStart('<span%s>');
        $this->setElementEnd('</span>');
        $this->setElementSeparator('<br />');
        
        return parent::formErrors($errors, $options);
    }
}