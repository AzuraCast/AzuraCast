<?php
namespace PVL\Controller\Action;

class Mobile extends \DF\Controller\Action
{
	public function init()
	{
    	parent::init();

		\Zend_Layout::getMvcInstance()->setLayout('mobile');

		header("Access-Control-Allow-Origin: *");
	}

	public function preDispatch()
	{
		parent::preDispatch();

		\Zend_Layout::getMvcInstance()->enableLayout();
	}
}