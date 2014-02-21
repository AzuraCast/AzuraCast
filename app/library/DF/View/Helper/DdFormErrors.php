<?php
namespace DF\View\Helper;
class DdFormErrors extends \Zend_View_Helper_FormErrors
{
	public function ddFormErrors($errors, array $options = null)
    {
		$this->setElementStart('<dd%s>');
		$this->setElementEnd('</dd>');
		$this->setElementSeparator('<br />');
	
		return parent::formErrors($errors, $options);
	}
}