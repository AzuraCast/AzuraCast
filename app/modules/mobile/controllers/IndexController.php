<?php
use \Entity\Station;

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
				$this->render('view_video');
			break;

			case "radio":
			default:
				$this->view->category = $this->categories['audio'];
				$this->render('view_radio');
			break;
		}
	}
}