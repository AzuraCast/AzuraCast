<?php
namespace Controller\Stations;

use App\Flash;
use AzuraCast\Radio\Backend\BackendAbstract;
use Doctrine\ORM\EntityManager;
use Entity;
use App\Http\Request;
use App\Http\Response;

class StreamersController
{
    /** @var EntityManager */
    protected $em;

    /** @var Flash */
    protected $flash;

    /** @var array */
    protected $form_config;

    /** @var Entity\Repository\StationStreamerRepository */
    protected $streamers_repo;

    /**
     * StreamersController constructor.
     * @param EntityManager $em
     * @param Flash $flash
     * @param array $form_config
     */
    public function __construct(EntityManager $em, Flash $flash, array $form_config)
    {
        $this->em = $em;
        $this->flash = $flash;
        $this->form_config = $form_config;

        $this->streamers_repo = $this->em->getRepository(Entity\StationStreamer::class);
    }

    public function indexAction(Request $request, Response $response, $station_id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var BackendAbstract $backend */
        $backend = $request->getAttribute('station_backend');

        if (!$backend->supportsStreamers()) {
            throw new \App\Exception(_('This feature is not currently supported on this station.'));
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        if (!$station->getEnableStreamers()) {
            if ($request->hasParam('enable')) {
                $station->setEnableStreamers(true);
                $this->em->persist($station);
                $this->em->flush();

                $this->flash->alert('<b>' . _('Streamers enabled!') . '</b><br>' . _('You can now set up streamer (DJ) accounts.'),
                    'green');

                return $response->redirectToRoute('stations:streamers:index', ['station' => $station_id]);
            }

            return $view->renderToResponse($response, 'stations/streamers/disabled');
        }

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository('Entity\Settings');

        return $view->renderToResponse($response, 'stations/streamers/index', [
            'server_url' => $settings_repo->getSetting('base_url', ''),
            'stream_port' => $backend->getStreamPort(),
            'streamers' => $station->getStreamers(),
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id, $id = null): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $form = new \App\Form($this->form_config);

        if (!empty($id)) {
            $record = $this->streamers_repo->findOneBy([
                'id' => $id,
                'station_id' => $station_id
            ]);
            $form->setDefaults($this->streamers_repo->toArray($record));
        } else {
            $record = null;
        }

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\StationStreamer)) {
                $record = new Entity\StationStreamer($station);
            }

            $this->streamers_repo->fromArray($record, $data);

            $this->em->persist($record);
            $this->em->flush();

            $this->em->refresh($station);

            $this->flash->alert('<b>' . _('Streamer account updated!') . '</b>', 'green');

            return $response->redirectToRoute('stations:streamers:index', ['station' => $station_id]);
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => ($id) ? _('Edit Streamer') : _('Add Streamer')
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $record = $this->em->getRepository(Entity\StationStreamer::class)->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if ($record instanceof Entity\StationStreamer) {
            $this->em->remove($record);
        }

        $this->em->flush();

        $this->em->refresh($station);

        $this->flash->alert('<b>' . _('Record deleted.') . '</b>', 'green');

        return $response->redirectToRoute('stations:streamers:index', ['station' => $station_id]);
    }
}