<?php
use \Entity\Station;
use \Entity\Podcast;
use \Entity\Schedule;

class Mobile_IndexController extends \PVL\Controller\Action\Mobile
{
	public function indexAction()
	{}

	public function viewAction()
	{
		$this->categories = \Entity\Station::getCategories();

    	$stations_raw = $this->em->createQuery('SELECT s FROM Entity\Station s WHERE s.is_active=1 ORDER BY s.weight ASC')
				->getArrayResult();

		$this->stations = array();
		foreach($stations_raw as $station)
		{
			$this->stations[$station['id']] = $station;

			if (isset($this->categories[$station['category']]))
				$this->categories[$station['category']]['stations'][] = $station;
		}

		$this->view->stations = $this->stations;
		$this->view->categories = $this->categories;

		$display_type = strtolower($this->_getParam('type', 'radio'));
		switch($display_type)
		{
			case "show":
				$podcasts = Podcast::fetchLatest();
    			$this->view->podcasts = $podcasts;

    			$this->render('view_show');
			break;

			case "video":
				$this->view->category = $this->categories['video'];
				$this->view->headTitle('Video Streams');

				$this->render('view_station');
			break;

			case "radio":
			default:
				$this->view->category = $this->categories['audio'];
				$this->view->headTitle('Radio Stations');

				$this->render('view_station');
			break;
		}
	}

	public function stationAction()
	{
		$id = (int)$this->_getParam('id');
		$station = Station::find($id);

		if (!($station instanceof Station))
			throw new \DF\Exception\DisplayOnly('Not found!');

		$this->view->station = $station;

		$threshold = time()+86400*7;
		$this->view->events = Schedule::getEventsInRange($station->id, time(), $threshold);
	}

	public function podcastAction()
	{
		$id = (int)$this->_getParam('id');
		$podcast = Podcast::find($id);

		if (!($podcast instanceof Podcast))
			throw new \DF\Exception\DisplayOnly('Not found!');

		$this->view->podcast = $podcast;
		$this->view->episodes = $podcast->episodes;
	}
}