<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class HlsStreamsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if (!$backend->supportsHls() || !$station->getEnableHls()) {
            throw new StationUnsupportedException();
        }

        $router = $request->getRouter();
        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsHlsStreams',
            id: 'station-hls-streams',
            title: __('HLS Streams'),
            props: [
                'listUrl' => (string)$router->fromHere('api:stations:hls_streams'),
                'restartStatusUrl' => (string)$router->fromHere('api:stations:restart-status'),
            ],
        );
    }
}
