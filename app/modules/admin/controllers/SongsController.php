<?php
namespace Modules\Admin\Controllers;

use Entity\Song;
use Entity\Song as Record;
use Entity\SongHistory;
use Entity\SongVote;

use PVL\Utilities;

class SongsController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer songs');
    }
    
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

    public function mergeAction()
    {
        if ($_GET)
            return $this->redirectFromHere($_GET);

        $id = $this->getParam('id');
        $song_to_merge = Record::find($id);

        if (!($song_to_merge instanceof Record))
            throw new \App\Exception('Song not found!');

        $this->view->song_to_merge = $song_to_merge;

        if ($this->hasParam('merge_id'))
        {
            $song_to_merge->merge_song_id = $this->getParam('merge_id');
            $song_to_merge->save();

            $this->alert('<b>Song merged.</b><br>Requests for this song ID will now show info from the merged song ID instead.', 'green');
            return $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'merge_q' => NULL, 'merge_id' => NULL));
        }
        else if ($this->hasParam('merge_q'))
        {
            $this->view->q = $q = trim($this->getParam('merge_q'));
            $query = $this->em->createQuery('SELECT s FROM Entity\Song s
                WHERE (s.text LIKE :q OR s.id = :q_exact)
                AND s.id != :song_to_merge
                AND s.merge_song_id IS NULL
                ORDER BY s.text ASC')
                ->setParameter('song_to_merge', $song_to_merge->id)
                ->setParameter('q', '%'.addcslashes($q, "%_").'%')
                ->setParameter('q_exact', $q);

            $this->view->results = $query->getArrayResult();
        }
    }

    public function unmergeAction()
    {
        $id = $this->getParam('id');
        $record = Record::find($id);

        if ($record instanceof Record)
        {
            $record->merge_song_id = NULL;
            $record->save();
        }

        $this->alert('<b>Song unmerged.</b>', 'green');
        return $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }

    public function editAction()
    {
        $form = new \App\Form($this->current_module_config->forms->song);

        if ($this->hasParam('id'))
        {
            $id = $this->getParam('id');
            $record = Record::find($id);
            $form->setDefaults($record->toArray(TRUE, TRUE));
        }

        if($_POST && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            if (!($record instanceof Record))
                $record = new Record;

            /*
            $files = $form->processFiles('songs');
            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];
            */

            $record->fromArray($data);
            $record->save();

            $this->alert('Changes saved.', 'green');

            return $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
        }

        $this->renderForm($form, 'edit', 'Edit Record');
    }

    public function deleteAction()
    {
        $record = Record::find($this->getParam('id'));
        if ($record instanceof Record)
            $record->delete();

        $this->alert('Record deleted.', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
    }

    public function votesAction()
    {
        $threshold = strtotime('-1 week');

        $votes_raw = $this->em->createQuery('SELECT sv.song_id, SUM(sv.vote) AS vote_total FROM Entity\SongVote sv WHERE sv.timestamp >= :threshold GROUP BY sv.song_id')
            ->setParameter('threshold', $threshold)
            ->getArrayResult();

        Utilities::orderBy($votes_raw, 'vote_total DESC');

        $votes = array();
        foreach($votes_raw as $row)
        {
            $row['song'] = Song::find($row['song_id']);
            $votes[] = $row;
        }

        $this->view->votes = $votes;
    }
}