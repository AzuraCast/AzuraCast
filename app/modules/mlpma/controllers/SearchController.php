<?php
use \Entity\ArchiveSong;
use \DF\Exception\DisplayOnly as ErrorMessage;

class Mlpma_SearchController extends \PVL\Controller\Action\Mlpma
{
	public function indexAction()
	{
		if ($this->_hasParam('search'))
		{
			$q = $this->_getParam('search');
			if (!empty($q))
			{
				$query = $this->em->createQuery('SELECT song FROM Entity\ArchiveSong song WHERE (song.album LIKE :q OR song.artist LIKE :q OR song.title LIKE :q)')
					->setParameter('q', '%'.$q.'%');
			}
		}
		elseif ($this->_hasParam('genre'))
		{
			$genre = $this->_getParam('genre');

			$query = $this->em->createQuery('SELECT song FROM Entity\ArchiveSong song LEFT JOIN song.genres genre WHERE genre.name = :genre ORDER BY song.album ASC, song.track_number ASC, song.title ASC')
				->setParameter('genre', $genre);
		}

		if (!$query)
			throw new ErrorMessage('Your search could not be performed as requested. Please try your search again.');

		$this->view->songs = $query->execute();
	}
}