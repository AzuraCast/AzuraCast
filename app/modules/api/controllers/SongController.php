<?php
namespace Modules\Api\Controllers;

use \Entity\Song;
use \Entity\SongHistory;
use \Entity\SongVote;

class SongController extends BaseController
{
    public function listAction()
    {
        $return = \DF\Cache::get('api_songs');

        if (!$return)
        {
            ini_set('memory_limit', '-1');

            $all_songs = Song::fetchArray();
            $return = array();

            foreach ($all_songs as $song)
                $return[$song['id']] = Song::api($song);

            \DF\Cache::save($return, 'api_songs', array(), 60);
        }

        return $this->returnSuccess($return);
    }

    public function indexAction()
    {
        if (!$this->hasParam('id'))
            return $this->listAction();

        $id = $this->getParam('id');

        $record = Song::find($id);

        if (!($record instanceof Song))
            return $this->returnError('Song not found.');

        $return = Song::api($record);
        $return['external'] = $record->getExternal();
        return $this->returnSuccess($return);
    }

    public function searchAction()
    {
        if (!$this->hasParam('q'))
            return $this->returnError('No query provided.');

        $q = trim($this->getParam('q'));
        $results_raw = $this->em->createQuery('SELECT s FROM Entity\Song s WHERE (s.text LIKE :q OR s.id = :q_exact) ORDER BY s.text ASC')
            ->setParameter('q', '%'.addcslashes($q, "%_").'%')
            ->setParameter('q_exact', $q)
            ->setMaxResults(50)
            ->getArrayResult();

        $results = array();

        foreach($results_raw as $row)
            $results[$row['id']] = Song::api($row);

        return $this->returnSuccess($results);
    }

    /**
     * Voting Functions
     */

    public function likeAction()
    {
        return $this->_vote(1);
    }
    public function dislikeAction()
    {
        return $this->_vote(0-1);
    }
    public function clearvoteAction()
    {
        return $this->_vote(0);
    }

    protected function _vote($value)
    {
        // Re-enable session creation.
        \DF\Session::enable();

        $sh_id = (int)$this->getParam('sh_id');
        $sh = SongHistory::find($sh_id);

        if ($sh instanceof SongHistory)
        {
            if ($value == 0)
                $vote_result = $sh->clearVote();
            else
                $vote_result = $sh->vote($value);

            if ($vote_result)
                return $this->returnSuccess('OK');
            else
                return $this->returnError('Vote could not be applied.');
        }
        else
        {
            return $this->returnError('Song history record not found.');
        }
    }
}