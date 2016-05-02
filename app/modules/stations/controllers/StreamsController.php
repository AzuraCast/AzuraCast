<?php
namespace Modules\Stations\Controllers;

use \Entity\Station;
use \Entity\StationStream;

class StreamsController extends BaseController
{
    public function indexAction()
    {
        $this->view->streams = $this->station->streams;
    }

    public function setdefaultAction()
    {
        $id = (int)$this->getParam('id');
        $this->station->setDefaultStream($id);

        $this->alert('<b>Default stream updated.</b>', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }

    public function editAction()
    {
        if ($this->station->category == 'video')
            $form_config = $this->current_module_config->forms->video_stream;
        else
            $form_config = $this->current_module_config->forms->radio_stream;

        $form = new \App\Form($form_config);

        if ($this->hasParam('id'))
        {
            $record = StationStream::getRepository()->findOneBy(array(
                'id' => $this->getParam('id'),
                'station_id' => $this->station->id
            ));
            $form->setDefaults($record->toArray());
        }

        if(!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            if (!($record instanceof StationStream))
            {
                $record = new StationStream;
                $record->station = $this->station;
            }

            $record->fromArray($data);
            $record->save();

            // Ensure at least one stream is default.
            $this->station->checkDefaultStream();

            // Clear station cache.
            \App\Cache::remove('stations');

            // Immediately load "Now Playing" data for the added/updated stream.
            if ($data['is_active'] == 0)
            {
                $record->save();

                $this->alert('<b>Stream updated, but is currently inactive.</b><br>The system will not retrieve Now Playing data about this stream until it is activated.', 'red');
            }
            elseif ($this->station->category == 'video')
            {
                $np = \App\NowPlaying::processVideoStream($record, $this->station, true);
                $record->save();

                if ($np['meta']['status'] == 'online')
                    $this->alert('<b>Stream updated, and currently showing as online.</b>', 'green');
                else
                    $this->alert('<b>Stream updated, but is currently offline.</b>', 'red');
            }
            else
            {
                $np = \App\NowPlaying::processAudioStream($record, $this->station, true);
                $record->save();

                if ($np['status'] != 'offline')
                {
                    $song = $np['current_song'];
                    $this->alert('<b>Stream updated and successfully connected.</b><br>The currently playing song is reporting as "'.$song['title'].'" by "'.$song['artist'].'" with '.$np['listeners']['current'].' tuned in.', 'green');
                }
                else
                {
                    $this->alert('<b>Stream updated, but is currently offline.</b><br>The system could not retrieve now-playing information about this stream. Verify that the station is online and the URLs are correct.', 'red');
                }
            }

            $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
            return;
        }

        $title = (($this->hasParam('id')) ? 'Edit' : 'Add').' Station Stream';
        $this->renderForm($form, 'edit', $title);
    }

    public function deleteAction()
    {
        $id = (int)$this->getParam('id');

        $record = StationStream::getRepository()->findOneBy(array(
            'id' => $id,
            'station_id' => $this->station->id
        ));

        if ($record instanceof StationStream)
            $record->delete();

        // Ensure at least one stream is default.
        $this->station->checkDefaultStream();

        $this->alert('<b>Record deleted.</b>', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }
}
