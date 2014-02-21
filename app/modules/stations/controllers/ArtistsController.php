<?php
use \Entity\Station;
use \Entity\StationManager;

use \Entity\Artist;

class Stations_ArtistsController extends \PVL\Controller\Action\Station
{
	public function allAction()
	{
		$artists = $this->em->createQuery('SELECT a, at FROM Entity\Artist a LEFT JOIN a.types at ORDER BY a.name ASC')
			->getArrayResult();

		$licenses = array();
		$interviews = array();

		foreach($artists as $artist)
		{
			if ($artist['license'] && $artist['license'] != "na")
				$licenses[$artist['license']][] = $artist;

			if ($artist['interviews'])
				$interviews[] = $artist;
		}

		$this->view->licenses = $licenses;
		$this->view->interviews = $interviews;
	}
	
	public function copyrightAction()
	{
		$artists = $this->em->createQuery('SELECT a, at FROM Entity\Artist a LEFT JOIN a.types at WHERE a.license = :full ORDER BY a.name ASC')
			->setParameter('full', 'full')
			->getArrayResult();
		$this->view->artists = $artists;
	}
}