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

        return $request->getView()->renderToResponse(
            $response,
            'stations/playlists/index',
            [
                'station_tz' => $station->getTimezone(),
                'enableAdvancedFeatures' => $settings->getEnableAdvancedFeatures(),
            ]
        );
    }
}
