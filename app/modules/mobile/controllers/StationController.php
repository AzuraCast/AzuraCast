<?php
use \Entity\Station;

class Mobile_StationController extends \PVL\Controller\Action\Mobile
{
	public function indexAction()
	{
		$id = (int)$this->_getParam('id');
		$station = Station::find($id);

		if (!($station instanceof Station))
			throw new \DF\Exception\DisplayOnly('Not found!');

		$this->view->station = $station;
	}
}