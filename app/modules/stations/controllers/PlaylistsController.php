<?php
namespace Modules\Stations\Controllers;

use Entity\Station;
use Entity\StationPlaylist;

class PlaylistsController extends BaseController
{
    protected function preDispatch()
    {
        if (!$this->backend->supportsMedia())
            throw new \App\Exception(_('This feature is not currently supported on this station.'));
        return parent::preDispatch();
    }

    protected function permissions()
    {
        return $this->acl->isAllowed('manage station media', $this->station->id);
    }

    public function indexAction()
    {
        $all_playlists = $this->station->playlists;

        $total_weights = 0;
        foreach($all_playlists as $playlist)
        {
            if ($playlist->is_enabled && $playlist->type == 'default')
                $total_weights += $playlist->weight;
        }

        $playlists = array();
        foreach($all_playlists as $playlist)
        {
            $playlist_row = $playlist->toArray($this->em);

            if ($playlist->is_enabled && $playlist->type == 'default')
                $playlist_row['probability'] = round(($playlist->weight / $total_weights) * 100, 1).'%';

            $playlist_row['num_songs'] = count($playlist->media);

            $playlists[$playlist->id] = $playlist_row;
        }

        $this->view->playlists = $playlists;
    }

    public function editAction()
    {
        $form_config = $this->current_module_config->forms->playlist;
        $form = new \App\Form($form_config);

        if ($this->hasParam('id'))
        {
            $record = $this->em->getRepository(StationPlaylist::class)->findOneBy(array(
                'id' => $this->getParam('id'),
                'station_id' => $this->station->id
            ));
            $form->setDefaults($record->toArray($this->em));
        }

        if(!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            if (!($record instanceof StationPlaylist))
            {
                $record = new StationPlaylist;
                $record->station = $this->station;
            }

            $record->fromArray($this->em, $data);

            $this->em->persist($record);

            $uow = $this->em->getUnitOfWork();
            $uow->computeChangeSets();
            if ($uow->isEntityScheduled($record))
            {
                $this->station->needs_restart = true;
                $this->em->persist($this->station);
            }

            $this->em->flush();
            $this->em->refresh($this->station);

            $this->alert('<b>'._('Record updated.').'</b>', 'green');

            return $this->redirectFromHere(['action' => 'index', 'id' => NULL]);
        }

        $this->view->form = $form;
        $this->view->title = ($this->hasParam('id')) ? _('Edit Record') : _('Add Record');
    }

    public function deleteAction()
    {
        $id = (int)$this->getParam('id');

        $record = $this->em->getRepository(StationPlaylist::class)->findOneBy(array(
            'id' => $id,
            'station_id' => $this->station->id
        ));

        if ($record instanceof StationPlaylist)
            $this->em->remove($record);

        $this->em->flush();
        $this->em->refresh($this->station);

        $this->alert('<b>'._('Record deleted.').'</b>', 'green');
        return $this->redirectFromHere(['action' => 'index', 'id' => NULL]);
    }
}
