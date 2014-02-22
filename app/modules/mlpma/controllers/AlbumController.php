<?php
use \Entity\ArchiveSong;
use \DF\Exception\DisplayOnly as ErrorMessage;

class Mlpma_AlbumController extends \PVL\Controller\Action\Mlpma
{
	public function indexAction()
	{
		$album_name = $this->_getParam('album');
		$album_artist = $this->_getParam('artist');

		$album = $this->em->createQuery('SELECT song FROM Entity\ArchiveSong song WHERE song.album = :album AND song.artist = :artist ORDER BY song.track_number ASC, song.title ASC')
			->setParameter('album', $album_name)
			->setParameter('artist', $album_artist)
			->execute();

		if (count($album) == 0)
			throw new ErrorMessage('Album not found!');

		$this->view->album_name = $album_name;
		$this->view->album_artist = $album_artist;
		$this->view->album = $album;
	}
}