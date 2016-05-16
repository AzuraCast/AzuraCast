<?php
namespace Modules\Stations\Controllers;

use Entity\Station;
use Entity\StationPlaylist;

class PlaylistsController extends BaseController
{
    public function indexAction()
    {
        $this->view->playlists = $this->station->playlists;
    }

    public function editAction()
    {
        $form_config = $this->current_module_config->forms->playlist;
        $form = new \App\Form($form_config);

        if ($this->hasParam('id'))
        {
            $record = StationPlaylist::getRepository()->findOneBy(array(
                'id' => $this->getParam('id'),
                'station_id' => $this->station->id
            ));
            $form->setDefaults($record->toArray());
        }

        if(!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            if (!($record instanceof StationPlaylist))
            {
                $record = new StationPlaylist;
                $record->station = $this->station;
            }

            $record->fromArray($data);
            $record->save();

            $this->alert('<b>Stream updated!</b>', 'green');

            return $this->redirectFromHere(['action' => 'index', 'id' => NULL]);
        }

        $title = (($this->hasParam('id')) ? 'Edit' : 'Add').' Playlist';
        return $this->renderForm($form, 'edit', $title);
    }

    public function deleteAction()
    {
        $id = (int)$this->getParam('id');

        $record = StationPlaylist::getRepository()->findOneBy(array(
            'id' => $id,
            'station_id' => $this->station->id
        ));

        if ($record instanceof StationPlaylist)
            $record->delete();

        $this->alert('<b>Record deleted.</b>', 'green');
        return $this->redirectFromHere(['action' => 'index', 'id' => NULL]);
    }
}
