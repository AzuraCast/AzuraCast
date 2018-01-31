<?php
namespace Controller\Stations;

use Entity;
use App\Http\Request;
use App\Http\Response;

class StreamersController extends \AzuraCast\Legacy\Controller
{
    /** @var Entity\Repository\StationStreamerRepository */
    protected $streamers_repo;

    protected function preDispatch()
    {
        if (!$this->backend->supportsStreamers()) {
            throw new \App\Exception(_('This feature is not currently supported on this station.'));
        }

        parent::preDispatch();

        $this->streamers_repo = $this->em->getRepository(Entity\StationStreamer::class);
    }

    public function indexAction(Request $request, Response $response): Response
    {
        if (!$this->station->getEnableStreamers()) {
            if ($this->hasParam('enable')) {
                $this->station->setEnableStreamers(true);
                $this->em->persist($this->station);
                $this->em->flush();

                $this->alert('<b>' . _('Streamers enabled!') . '</b><br>' . _('You can now set up streamer (DJ) accounts.'),
                    'green');

                return $this->redirectFromHere(['enable' => null]);
            } else {
                return $this->render('controller::disabled');
            }
        }

        $this->view->server_url = $this->em->getRepository('Entity\Settings')->getSetting('base_url', '');
        $this->view->stream_port = $this->backend->getStreamPort();

        $this->view->streamers = $this->station->getStreamers();
    }

    public function editAction(Request $request, Response $response): Response
    {
        $form_config = $this->config->forms->streamer;
        $form = new \App\Form($form_config);

        if ($this->hasParam('id')) {
            $record = $this->streamers_repo->findOneBy([
                'id' => $this->getParam('id'),
                'station_id' => $this->station->getId()
            ]);
            $form->setDefaults($this->streamers_repo->toArray($record));
        } else {
            $record = null;
        }

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\StationStreamer)) {
                $record = new Entity\StationStreamer($this->station);
            }

            $this->streamers_repo->fromArray($record, $data);

            $this->em->persist($record);
            $this->em->flush();

            $this->em->refresh($this->station);

            $this->alert('<b>' . _('Streamer account updated!') . '</b>', 'green');

            return $this->redirectFromHere(['action' => 'index', 'id' => null]);
        }

        $title = (($this->hasParam('id')) ? _('Edit Streamer') : _('Add Streamer'));

        return $this->renderForm($form, 'edit', $title);
    }

    public function deleteAction(Request $request, Response $response): Response
    {
        $id = (int)$this->getParam('id');

        $record = $this->em->getRepository(Entity\StationStreamer::class)->findOneBy([
            'id' => $id,
            'station_id' => $this->station->getId()
        ]);

        if ($record instanceof Entity\StationStreamer) {
            $this->em->remove($record);
        }

        $this->em->flush();

        $this->em->refresh($this->station);

        $this->alert('<b>' . _('Record deleted.') . '</b>', 'green');

        return $this->redirectFromHere(['action' => 'index', 'id' => null]);
    }
}