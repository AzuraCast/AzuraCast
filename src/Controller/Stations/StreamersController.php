<?php
namespace App\Controller\Stations;

use App\Entity;
use App\Exception\StationUnsupportedException;
use App\Form\EntityFormManager;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\AzuraCastCentral;
use Azura\Config;
use Azura\Session\Flash;
use Psr\Http\Message\ResponseInterface;

class StreamersController extends AbstractStationCrudController
{
    /** @var AzuraCastCentral */
    protected AzuraCastCentral $ac_central;

    /** @var Entity\Repository\SettingsRepository */
    protected Entity\Repository\SettingsRepository $settingsRepo;

    /**
     * @param EntityFormManager $formManager
     * @param Config $config
     * @param AzuraCastCentral $ac_central
     * @param Entity\Repository\SettingsRepository $settingsRepo
     */
    public function __construct(
        EntityFormManager $formManager,
        Config $config,
        AzuraCastCentral $ac_central,
        Entity\Repository\SettingsRepository $settingsRepo
    ) {
        $form = $formManager->getForm(Entity\StationStreamer::class, $config->get('forms/streamer'));
        parent::__construct($form);

        $this->ac_central = $ac_central;
        $this->settingsRepo = $settingsRepo;
        $this->csrf_namespace = 'stations_streamers';
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if (!$backend::supportsStreamers()) {
            throw new StationUnsupportedException;
        }

        $view = $request->getView();

        if (!$station->getEnableStreamers()) {
            $params = $request->getQueryParams();
            if (isset($params['enable'])) {
                $station->setEnableStreamers(true);
                $this->em->persist($station);
                $this->em->flush();

                $request->getFlash()->addMessage('<b>' . __('Streamers enabled!') . '</b><br>' . __('You can now set up streamer (DJ) accounts.'),
                    Flash::SUCCESS);

                return $response->withRedirect($request->getRouter()->fromHere('stations:streamers:index'));
            }

            return $view->renderToResponse($response, 'stations/streamers/disabled');
        }

        $be_settings = (array)$station->getBackendConfig();

        return $view->renderToResponse($response, 'stations/streamers/index', [
            'server_url' => $this->settingsRepo->getSetting(Entity\Settings::BASE_URL, ''),
            'stream_port' => $backend->getStreamPort($station),
            'ip' => $this->ac_central->getIp(),
            'streamers' => $station->getStreamers(),
            'dj_mount_point' => $be_settings['dj_mount_point'] ?? '/',
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            $request->getFlash()->addMessage('<b>' . ($id ? __('Streamer updated.') : __('Streamer added.')) . '</b>',
                Flash::SUCCESS);
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
        $id,
        $csrf
    ): ResponseInterface {
        $this->_doDelete($request, $id, $csrf);

        $request->getFlash()->addMessage('<b>' . __('Streamer deleted.') . '</b>', Flash::SUCCESS);
        return $response->withRedirect($request->getRouter()->fromHere('stations:streamers:index'));
    }
}
