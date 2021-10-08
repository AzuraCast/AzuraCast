<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\AzuraCastCentral;
use App\Session\Flash;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class StreamersAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        AzuraCastCentral $acCentral,
        Entity\Repository\SettingsRepository $settingsRepo
    ): ResponseInterface {
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if (!$backend->supportsStreamers()) {
            throw new StationUnsupportedException();
        }

        $view = $request->getView();

        if (!$station->getEnableStreamers()) {
            $params = $request->getQueryParams();
            if (isset($params['enable'])) {
                $station->setEnableStreamers(true);
                $em->persist($station);
                $em->flush();

                $request->getFlash()->addMessage(
                    '<b>' . __('Streamers enabled!') . '</b><br>' . __('You can now set up streamer (DJ) accounts.'),
                    Flash::SUCCESS
                );

                return $response->withRedirect((string)$request->getRouter()->fromHere('stations:streamers:index'));
            }

            return $view->renderToResponse($response, 'stations/streamers/disabled');
        }

        $settings = $settingsRepo->readSettings();
        $backendConfig = $station->getBackendConfig();

        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsStreamers',
            id: 'station-streamers',
            title: __('Streamer/DJ Accounts'),
            props: [
                'listUrl' => (string)$router->fromHere('api:stations:streamers'),
                'scheduleUrl' => (string)$router->fromHere('api:stations:streamers:schedule'),
                'stationTimeZone' => $station->getTimezone(),
                'connectionInfo' => [
                    'serverUrl' => $settings->getBaseUrl(),
                    'streamPort' => $backend->getStreamPort($station),
                    'ip' => $acCentral->getIp(),
                    'djMountPoint' => $backendConfig->getDjMountPoint(),
                ],
            ]
        );
    }
}
