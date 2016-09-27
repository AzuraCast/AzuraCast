<?php
namespace Modules\Stations\Controllers;

use Entity\Settings;
use Entity\Station;
use Entity\StationStreamer;
use Entity\StationStreamer as Record;

class StreamersController extends BaseController
{
    public function indexAction()
    {
        if (!$this->station->enable_streamers)
        {
            if ($this->hasParam('enable'))
            {
                $this->station->enable_streamers = true;

                $this->em->persist($this->station);
                $this->em->flush();

                $this->alert('<b>Streamers enabled!</b><br>You can now set up streamer (DJ) accounts.', 'green');

                return $this->redirectFromHere(['enable' => null]);
            }
            else
            {
                return $this->render('controller::disabled');
            }
        }

        $this->view->server_url = Settings::getSetting('base_url', '');
        $this->view->streamers = $this->station->streamers;
    }

    public function editAction()
    {
        $form_config = $this->current_module_config->forms->streamer;
        $form = new \App\Form($form_config);

        if ($this->hasParam('id'))
        {
            $record = Record::getRepository()->findOneBy(array(
                'id' => $this->getParam('id'),
                'station_id' => $this->station->id
            ));
            $form->setDefaults($record->toArray($this->em));
        }

        if(!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            if (!($record instanceof Record))
            {
                $record = new Record;
                $record->station = $this->station;
            }

            $record->fromArray($this->em, $data);
            $this->em->persist($record);
            $this->em->flush();

            $this->alert('<b>Streamer account updated!</b>', 'green');

            return $this->redirectFromHere(['action' => 'index', 'id' => NULL]);
        }

        $title = (($this->hasParam('id')) ? 'Edit' : 'Add').' Streamer (DJ) Account';
        return $this->renderForm($form, 'edit', $title);
    }

    public function deleteAction()
    {
        $id = (int)$this->getParam('id');

        $record = Record::getRepository()->findOneBy(array(
            'id' => $id,
            'station_id' => $this->station->id
        ));

        if ($record instanceof Record)
            $this->em->remove($record);

        $this->em->flush();

        $this->alert('<b>Record deleted.</b>', 'green');
        return $this->redirectFromHere(['action' => 'index', 'id' => NULL]);
    }
}