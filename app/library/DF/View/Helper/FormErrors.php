<?php
namespace DF\View\Helper;
class FormErrors extends HelperAbstract
{
    public function formErrors($errors, array $options = null)
    {
        $this->setElementStart('<dd%s>');
        $this->setElementEnd('</dd>');
        $this->setElementSeparator('<br />');
    
        return parent::formErrors($errors, $options);
    }
}