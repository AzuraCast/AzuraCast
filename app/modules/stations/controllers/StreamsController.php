<?php
use \Entity\Station;
use \Entity\StationStream;

class Stations_StreamsController extends \PVL\Controller\Action\Station
{
    public function indexAction()
    {
        $this->view->streams = $this->station->streams;
    }

    public function setdefaultAction()
    {
        $id = (int)$this->getParam('id');
        $this->station->setDefaultStream($id);

        $this->alert('<b>Record deleted.</b>', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }

    public function editAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->stream);

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

            // Immediately load "Now Playing" data for the added/updated stream.
            $np = \PVL\NowPlaying::processStream($record, $this->station);

            if ($np[''])


            $this->alert('Stream updated.', 'green');
            $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
            return;
        }

        if ($this->hasParam('id'))
            $this->view->headTitle('Edit Station Stream');
        else
            $this->view->headTitle('Add Station Stream');

        $this->renderForm($form);
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
