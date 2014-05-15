<?php
use \Entity\Podcast;
use \Entity\PodcastEpisode;

class ShowController extends \DF\Controller\Action
{
	public function indexAction()
	{
		$podcasts = $this->em->createQuery('SELECT p, s, pe FROM Entity\Podcast p LEFT JOIN p.stations s LEFT JOIN p.episodes pe WHERE p.is_approved = 1 ORDER BY p.name ASC')
			->getArrayResult();

		$this->view->podcasts = $podcasts;
	}

	public function viewAction()
	{
		$id = (int)$this->_getParam('id');
    	$podcast = Podcast::find($id);

    	if (!($podcast instanceof Podcast))
    		throw new \DF\Exception\DisplayOnly('Podcast not found!');

    	$this->view->podcast = $podcast;
    	$this->view->episodes = $podcast->episodes;
	}
}