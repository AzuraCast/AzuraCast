<?php
use \Entity\ArchiveSong;
use \Entity\ArchiveGenre;

class Mlpma_IndexController extends \PVL\Controller\Action\Mlpma
{
    public function indexAction()
    {
        $this->view->newest_songs = $this->em->createQuery('SELECT song FROM Entity\ArchiveSong song ORDER BY song.id DESC')
            ->setMaxResults(10)
            ->execute();

        $this->view->genres = ArchiveGenre::getTop(25);
    }

    public function downloadAction()
    {}
}