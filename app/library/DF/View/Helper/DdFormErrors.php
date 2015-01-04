<?php
namespace DF\View\Helper;

class DdFormErrors extends HelperAbstract
{
    public function ddFormErrors($errors, array $options = null)
    {
        $this->setElementStart('<dd%s>');
        $this->setElementEnd('</dd>');
        $this->setElementSeparator('<br />');
    
        return parent::formErrors($errors, $options);
    }
}