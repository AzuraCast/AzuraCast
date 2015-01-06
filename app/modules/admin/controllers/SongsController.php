<?php
namespace Modules\Admin\Controllers;

use \Entity\Song;
use \Entity\Song as Record;
use \Entity\SongHistory;
use \Entity\SongVote;

class SongsController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer stations');
    }
    
    public function indexAction()
    {
    }

    public function votesAction()
    {
        $threshold = strtotime('-1 week');

        $votes_raw = $this->em->createQuery('SELECT sv.song_id, SUM(sv.vote) AS vote_total FROM Entity\SongVote sv WHERE sv.timestamp >= :threshold GROUP BY sv.song_id')
            ->setParameter('threshold', $threshold)
            ->getArrayResult();

        \PVL\Utilities::orderBy($votes_raw, 'vote_total DESC');

        $votes = array();
        foreach($votes_raw as $row)
        {
            $row['song'] = Song::find($row['song_id']);
            $votes[] = $row;
        }

        $this->view->votes = $votes;
    }
}