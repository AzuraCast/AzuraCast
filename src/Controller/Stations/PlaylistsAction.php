<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PlaylistsAction implements SingleActionInterface
{
    use SettingsAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $settings = $this->readSettings();
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Stations/Playlists',
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
