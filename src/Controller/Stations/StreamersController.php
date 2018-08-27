<?php
namespace App\Controller\Stations;

use App\Radio\Backend\BackendAbstract;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class StreamersController
{
    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $csrf_namespace = 'stations_streamers';

    /** @var array */
    protected $form_config;

    /** @var Entity\Repository\StationStreamerRepository */
    protected $streamers_repo;

    /**
     * @param EntityManager $em
     * @param array $form_config
     * @see \App\Provider\StationsProvider
     */
    public function __construct(EntityManager $em, array $form_config)
    {
        $this->em = $em;
        $this->form_config = $form_config;

        $this->streamers_repo = $this->em->getRepository(Entity\StationStreamer::class);
    }

    public function indexAction(Request $request, Response $response, $station_id): Response
    {
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if (!$backend->supportsStreamers()) {
            throw new \App\Exception(__('This feature is not currently supported on this station.'));
        }

        $view = $request->getView();

        if (!$station->getEnableStreamers()) {
            if ($request->hasParam('enable')) {
                $station->setEnableStreamers(true);
                $this->em->persist($station);
                $this->em->flush();

                $request->getSession()->flash('<b>' . __('Streamers enabled!') . '</b><br>' . __('You can now set up streamer (DJ) accounts.'),
                    'green');

                return $response->withRedirect($request->getRouter()->fromHere('stations:streamers:index'));
            }

            return $view->renderToResponse($response, 'stations/streamers/disabled');
        }

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        return $view->renderToResponse($response, 'stations/streamers/index', [
            'server_url' => $settings_repo->getSetting('base_url', ''),
            'stream_port' => $backend->getStreamPort(),
            'streamers' => $station->getStreamers(),
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id, $id = null): Response
    {
        $station = $request->getStation();

        $form = new \AzuraForms\Form($this->form_config);

        if (!empty($id)) {
            $record = $this->streamers_repo->findOneBy([
                'id' => $id,
                'station_id' => $station_id
            ]);
            $form->populate($this->streamers_repo->toArray($record));
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

            $request->getSession()->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Streamer')) . '</b>', 'green');

            return $response->withRedirect($request->getRouter()->fromHere('stations:streamers:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Streamer'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): Response
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $station = $request->getStation();

        $record = $this->em->getRepository(Entity\StationStreamer::class)->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if ($record instanceof Entity\StationStreamer) {
            $this->em->remove($record);
        }

        $this->em->flush();

        $this->em->refresh($station);

        $request->getSession()->flash('<b>' . __('%s deleted.', __('Streamer')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->fromHere('stations:streamers:index'));
    }
}
