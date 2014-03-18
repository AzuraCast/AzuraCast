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
		$this->returnToScreen(array(
			'status' 	=> 'success',
			'result'	=> $data,
		));
	}

	public function returnError($message)
	{
		$this->returnToScreen(array(
			'status' 	=> 'error',
			'error'		=> $message,
		));
	}

	public function returnToScreen($obj)
	{
		$format = strtolower($this->_getParam('format', 'json'));

		switch($format)
		{
			case "xml":
				header('Content-Type: text/xml');
				echo \DF\Export::ArrayToXml($obj);
			break;

			case "json":
			default:
				header('Content-Type: application/json');
				echo json_encode($obj, JSON_UNESCAPED_SLASHES);
			break;
		}
	}

}