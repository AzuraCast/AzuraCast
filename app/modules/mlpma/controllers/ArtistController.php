<?php
use \Entity\ArchiveSong;
use \DF\Exception\DisplayOnly as ErrorMessage;

class Mlpma_ArtistController extends \PVL\Controller\Action\Mlpma
{
	public function indexAction()
	{
		$artist = $this->_getParam('artist');

		$albums = array();
		$songs = $this->em->createQuery('SELECT song FROM Entity\ArchiveSong song WHERE song.artist = :artist ORDER BY song.album ASC, song.track_number ASC, song.title ASC')
			->setParameter('artist', $artist)
			->execute();

		if (count($songs) == 0)
			throw new ErrorMessage('Artist not found!');

		foreach($songs as $song)
		{
			$album_name = $song->album;
			$albums[$album_name][] = $song;
		}

		$this->view->artist_name = $artist;
		$this->view->albums = $albums;
	}
}