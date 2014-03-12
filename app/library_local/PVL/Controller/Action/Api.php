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
		header('Content-Type: application/json');
		header('Access-Control-Allow-Origin: *');

		// Log request.

	}

	/**
	 * Authentication
	 */

	public function requireKey()
	{
		$this->returnError('API keys are not yet implemented.');
		return;
	}

	/**
	 * Result Printout
	 */

	public function returnSuccess($data)
	{
		$this->returnJson(array(
			'status' 	=> 'success',
			'result'	=> $data,
		));
	}

	public function returnError($message)
	{
		$this->returnJson(array(
			'status' 	=> 'error',
			'error'		=> $message,
		));
	}

	public function returnJson($obj)
	{
		echo json_encode($obj, JSON_UNESCAPED_SLASHES);
	}



}