<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\Repository\SettingsRepository;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class PlaylistsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        SettingsRepository $settingsRepo
    ): ResponseInterface {
        $station = $request->getStation();

        $backend = $request->getStationBackend();
        if (!$backend->supportsMedia()) {
            throw new Exception(__('This feature is not currently supported on this station.'));
        }

        $settings = $settingsRepo->readSettings();
        $router = $request->getRouter();
        $customization = $request->getCustomization();

        return $request->getView()->renderToResponse(
            $response,
            'system/vue',
            [
                'title' => __('Playlists'),
                'id' => 'station-playlist',
                'component' => 'Vue_StationsPlaylists',
                'props' => [
                    'listUrl' => (string)$router->fromHere('api:stations:playlists'),
                    'scheduleUrl' => (string)$router->fromHere('api:stations:playlists:schedule'),
                    'locale' => substr($customization->getLocale()->getLocale(), 0, 2),
                    'filesUrl' => (string)$router->fromHere('stations:files:index'),
                    'stationTimeZone' => $station->getTimezone(),
                    'enableAdvancedFeatures' => $settings->getEnableAdvancedFeatures(),
                ],
            ]
        );
    }
}
