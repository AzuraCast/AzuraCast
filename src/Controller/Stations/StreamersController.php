<?php
namespace App\Controller\Stations;

use App\Entity;
use App\Form\EntityFormManager;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Azura\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    public function indexAction(ServerRequestInterface $request, ResponseInterface $response, $station_id): ResponseInterface
    {
        $station = RequestHelper::getStation($request);
        $backend = RequestHelper::getStationBackend($request);

        if (!$backend::supportsStreamers()) {
            throw new \App\Exception\StationUnsupported;
        }

        $view = RequestHelper::getView($request);

        if (!$station->getEnableStreamers()) {
            $params = $request->getQueryParams();
            if (isset($params['enable'])) {
                $station->setEnableStreamers(true);
                $this->em->persist($station);
                $this->em->flush();

                RequestHelper::getSession($request)->flash('<b>' . __('Streamers enabled!') . '</b><br>' . __('You can now set up streamer (DJ) accounts.'),
                    'green');

                return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->fromHere('stations:streamers:index'));
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
            'csrf' => RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            RequestHelper::getSession($request)->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Streamer')) . '</b>', 'green');
            return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->fromHere('stations:streamers:index'));
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Streamer'))
        ]);
    }

    public function deleteAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        RequestHelper::getSession($request)->flash('<b>' . __('%s deleted.', __('Streamer')) . '</b>', 'green');
        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->fromHere('stations:streamers:index'));
    }
}
