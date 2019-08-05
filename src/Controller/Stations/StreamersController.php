<?php
namespace App\Controller\Stations;

use App\Form\EntityFormManager;
use Azura\Config;
use App\Entity;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseInterface;

class StreamersController extends AbstractStationCrudController
{
    /**
     * @param EntityFormManager $formManager
     * @param Config $config
     */
    public function __construct(EntityFormManager $formManager, Config $config)
    {
        $form = $formManager->getForm(Entity\StationStreamer::class, $config->get('forms/streamer'));
        parent::__construct($form);

        $this->csrf_namespace = 'stations_streamers';
    }

    public function indexAction(Request $request, Response $response, $station_id): ResponseInterface
    {
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if (!$backend::supportsStreamers()) {
            throw new \App\Exception\StationUnsupported;
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

        $be_settings = (array)$station->getBackendConfig();

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        return $view->renderToResponse($response, 'stations/streamers/index', [
            'server_url' => $settings_repo->getSetting(Entity\Settings::BASE_URL, ''),
            'stream_port' => $backend->getStreamPort($station),
            'streamers' => $station->getStreamers(),
            'dj_mount_point' => $be_settings['dj_mount_point'] ?? '/',
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            $request->getSession()->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Streamer')) . '</b>', 'green');
            return $response->withRedirect($request->getRouter()->fromHere('stations:streamers:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Streamer'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        $request->getSession()->flash('<b>' . __('%s deleted.', __('Streamer')) . '</b>', 'green');
        return $response->withRedirect($request->getRouter()->fromHere('stations:streamers:index'));
    }
}
