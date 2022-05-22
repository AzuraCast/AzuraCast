<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\Repository\SettingsRepository;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PlaylistsAction
{
    public function __construct(
        private readonly SettingsRepository $settingsRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        int|string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        $backend = $request->getStationBackend();
        if (!$backend->supportsMedia()) {
            throw new Exception(__('This feature is not currently supported on this station.'));
        }

        $settings = $this->settingsRepo->readSettings();
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsPlaylists',
            id: 'station-playlist',
            title: __('Playlists'),
            props: [
                'listUrl' => (string)$router->fromHere('api:stations:playlists'),
                'scheduleUrl' => (string)$router->fromHere('api:stations:playlists:schedule'),
                'filesUrl' => (string)$router->fromHere('stations:files:index'),
                'restartStatusUrl' => (string)$router->fromHere('api:stations:restart-status'),
                'stationTimeZone' => $station->getTimezone(),
                'useManualAutoDj' => $station->useManualAutoDJ(),
                'enableAdvancedFeatures' => $settings->getEnableAdvancedFeatures(),
            ],
        );
    }
}
