<?php
namespace Controller\Stations;

use Entity\Settings;
use Entity\Station;
use Entity\StationStreamer;
use Entity\StationStreamer as Record;

class StreamersController extends BaseController
{
    protected function preDispatch()
    {
        if (!$this->frontend->supportsStreamers())
            throw new \App\Exception(_('This feature is not currently supported on this station.'));

        return parent::preDispatch();
    }

    protected function permissions()
    {
        return $this->acl->isAllowed('manage station streamers', $this->station->id);
    }

    public function indexAction()
    {
        if (!$this->station->enable_streamers)
        {
            if ($this->hasParam('enable'))
            {
                $this->station->enable_streamers = true;

                $this->em->persist($this->station);
                $this->em->flush();

                $this->alert('<b>'._('Streamers enabled!').'</b><br>'._('You can now set up streamer (DJ) accounts.'), 'green');

                return $this->redirectFromHere(['enable' => null]);
            }
            else
            {
                return $this->render('controller::disabled');
            }
        }

        $this->view->server_url = $this->em->getRepository('Entity\Settings')->getSetting('base_url', '');
        $this->view->streamers = $this->station->streamers;
    }

    public function editAction()
    {
        $form_config = $this->config->forms->streamer;
        $form = new \App\Form($form_config);

        if ($this->hasParam('id'))
        {
            $record = $this->em->getRepository(Record::class)->findOneBy(array(
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

            $this->em->refresh($this->station);

            $this->alert('<b>'._('Streamer account updated!').'</b>', 'green');

            return $this->redirectFromHere(['action' => 'index', 'id' => NULL]);
        }

        $title = (($this->hasParam('id')) ? _('Edit Streamer') : _('Add Streamer'));
        return $this->renderForm($form, 'edit', $title);
    }

    public function deleteAction()
    {
        $id = (int)$this->getParam('id');

        $record = $this->em->getRepository(Record::class)->findOneBy(array(
            'id' => $id,
            'station_id' => $this->station->id
        ));

        if ($record instanceof Record)
            $this->em->remove($record);

        $this->em->flush();

        $this->em->refresh($this->station);

        $this->alert('<b>'._('Record deleted.').'</b>', 'green');
        return $this->redirectFromHere(['action' => 'index', 'id' => NULL]);
    }
}