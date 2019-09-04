<?php
namespace App\Controller\Stations;

use App\Entity;
use App\Exception\StationUnsupported;
use App\Form\EntityFormManager;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\AzuraCastCentral;
use Azura\Config;
use Psr\Http\Message\ResponseInterface;

class StreamersController extends AbstractStationCrudController
{
    /** @var AzuraCastCentral */
    protected $ac_central;

    /**
     * @param EntityFormManager $formManager
     * @param Config $config
     * @param AzuraCastCentral $ac_central
     */
    public function __construct(
        EntityFormManager $formManager,
        Config $config,
        AzuraCastCentral $ac_central
    ) {
        $form = $formManager->getForm(Entity\StationStreamer::class, $config->get('forms/streamer'));
        parent::__construct($form);

        $this->ac_central = $ac_central;
        $this->csrf_namespace = 'stations_streamers';
    }

    public function indexAction(ServerRequest $request, Response $response, $station_id): ResponseInterface
    {
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if (!$backend::supportsStreamers()) {
            throw new StationUnsupported;
        }

        $view = $request->getView();

        if (!$station->getEnableStreamers()) {
            $params = $request->getQueryParams();
            if (isset($params['enable'])) {
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
            'ip' => $this->ac_central->getIp(),
            'streamers' => $station->getStreamers(),
            'dj_mount_point' => $be_settings['dj_mount_point'] ?? '/',
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, $station_id, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            $request->getSession()->flash('<b>' . ($id ? __('Streamer updated.') : __('Streamer added.')) . '</b>',
                'green');
            return $response->withRedirect($request->getRouter()->fromHere('stations:streamers:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => $id ? __('Edit Streamer') : __('Add Streamer'),
        ]);
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        $station_id,
        $id,
        $csrf_token
    ): ResponseInterface {
        $this->_doDelete($request, $id, $csrf_token);

        $request->getSession()->flash('<b>' . __('Streamer deleted.') . '</b>', 'green');
        return $response->withRedirect($request->getRouter()->fromHere('stations:streamers:index'));
    }
}
