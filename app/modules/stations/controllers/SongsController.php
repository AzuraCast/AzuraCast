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

            $this->view->pager = new \DF\Paginator\Doctrine($query, $this->getParam('page', 1), 30);
        }
    }

    public function newAction()
    {
        $new_threshold = strtotime('-2 weeks');
        $song_adapters = Song::getExternalAdapters();

        $new_songs_raw = $this->em->createQuery('SELECT s, ex_eqbeats, ex_btunes, ex_pfm
            FROM Entity\Song s
            LEFT JOIN s.external_eqbeats AS ex_eqbeats
            LEFT JOIN s.external_bronytunes AS ex_btunes
            LEFT JOIN s.external_ponyfm AS ex_pfm
            WHERE (ex_eqbeats.created >= :threshold OR ex_btunes.created >= :threshold OR ex_pfm.created >= :threshold)')
            ->setParameter('threshold', $new_threshold)
            ->getArrayResult();

        $new_songs = array();

        foreach($new_songs_raw as $song)
        {
            $timestamps = array();
            foreach($song_adapters as $adapter_key => $adapter_class)
            {
                $local_key = 'external_'.$adapter_key;
                if (!empty($song[$local_key]))
                    $timestamps[] = $song[$local_key]['created'];
            }

            $song['created'] = max($timestamps);
            $song['filename'] = $song['artist'].' - '.$song['title'];

            $new_songs[] = $song;
        }

        $new_songs = Utilities::irsort($new_songs, 'timestamp');
        $this->view->new_songs = $new_songs;
    }
}