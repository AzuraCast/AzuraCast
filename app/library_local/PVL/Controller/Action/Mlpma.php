<?php
namespace PVL\Controller\Action;

class Mlpma extends \DF\Controller\Action
{
	public function init()
	{
    parent::init();

		\Zend_Layout::getMvcInstance()->setLayout('mlpma');
	}

	public function preDispatch()
	{
		parent::preDispatch();
		
		\Zend_Layout::getMvcInstance()->enableLayout();
	}
}