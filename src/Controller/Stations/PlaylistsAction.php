<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\Repository\SettingsRepository;
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
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        $settings = $this->settingsRepo->readSettings();
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsPlaylists',
            id: 'station-playlist',
            title: __('Playlists'),
            props: [
                'listUrl' => $router->fromHere('api:stations:playlists'),
                'scheduleUrl' => $router->fromHere('api:stations:playlists:schedule'),
                'filesUrl' => $router->fromHere('stations:files:index'),
                'restartStatusUrl' => $router->fromHere('api:stations:restart-status'),
                'stationTimeZone' => $station->getTimezone(),
                'useManualAutoDj' => $station->useManualAutoDJ(),
                'enableAdvancedFeatures' => $settings->getEnableAdvancedFeatures(),
            ],
        );
    }
}
