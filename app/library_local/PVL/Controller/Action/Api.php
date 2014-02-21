<?php
namespace PVL\Controller\Action;

class Api extends \DF\Controller\Action
{
	public function permissions()
	{
		return true;
	}

	public function preDispatch()
	{
		parent::preDispatch();

		// Disable rendering.
		$this->doNotRender();
		


		// Allow AJAX retrieval.
		header('Access-Control-Allow-Origin: *');

	}

}