<?php
namespace DF\View\Helper;
class FormErrors extends \Zend_View_Helper_FormErrors
{
	public function formErrors($errors, array $options = null)
    {
		$this->setElementStart('<dd%s>');
		$this->setElementEnd('</dd>');
		$this->setElementSeparator('<br />');
	
		return parent::formErrors($errors, $options);
	}
}