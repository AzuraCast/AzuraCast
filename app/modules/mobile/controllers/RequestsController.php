<?php
use \Entity\Station;
use \Entity\StationMedia;

class Mobile_RequestsController extends \PVL\Controller\Action\Mobile
{
	public function indexAction()
	{
		$stations_supporting_requests = $this->em->createQuery('SELECT s FROM Entity\Station s WHERE s.requests_enabled = 1 ORDER BY s.weight ASC')
			->getArrayResult();

		$this->view->stations = $stations_supporting_requests;
	}
}