<?php
namespace Modules\Stations\Controllers;

use Entity\Station;
use Entity\Song;
use Entity\Song as Record;

use PVL\Utilities;

class SongsController extends BaseController
{
    public function indexAction()
    {
        if ($_GET)
            return $this->redirectFromHere($_GET);

        if ($this->hasParam('q'))
        {
            $this->view->q = $q = trim($this->getParam('q'));
            $query = $this->em->createQuery('SELECT s FROM Entity\Song s WHERE (s.text LIKE :q OR s.id = :q_exact) ORDER BY s.text ASC')
                ->setParameter('q', '%'.addcslashes($q, "%_").'%')
                ->setParameter('q_exact', $q);

            $this->view->pager = new \App\Paginator\Doctrine($query, $this->getParam('page', 1), 30);
        }
    }
}