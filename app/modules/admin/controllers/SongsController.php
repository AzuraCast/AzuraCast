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
        if ($_GET)
            $this->redirectFromHere($_GET);

        if ($this->_hasParam('q'))
        {
            $this->view->q = $q = trim($this->_getParam('q'));
            $query = $this->em->createQuery('SELECT s FROM Entity\Song s WHERE (s.text LIKE :q OR s.id = :q_exact) ORDER BY s.text ASC')
                ->setParameter('q', '%'.addcslashes($q, "%_").'%')
                ->setParameter('q_exact', $q);

            $this->view->pager = new \DF\Paginator\Doctrine($query, $this->_getParam('page', 1), 30);
        }
    }

    public function searchAction()
    {
        $this->doNotRender();

        $results = array();

        $query = $this->getParam('q');
        $results_raw = $this->em->createQuery('SELECT s FROM Entity\Song s WHERE s.text LIKE :query')
            ->setParameter('query', '%'.addcslashes($query, "%_").'%')
            ->getArrayResult();

        if ($results_raw)
        {
            foreach($results_raw as $row)
                $results[] = array('label' => $row['title'].'<br>'.$row['artist'], 'value' => $row['id']);
        }

        $this->response->setJsonContent($results);
        return $this->response->send();
    }

    public function editAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->song);

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

            $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
            return;
        }

        $this->renderForm($form, 'edit', 'Edit Record');
    }

    public function deleteAction()
    {
        $record = Record::find($this->_getParam('id'));
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