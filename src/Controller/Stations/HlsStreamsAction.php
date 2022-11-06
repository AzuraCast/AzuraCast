<?php

declare(strict_types=1);

namespace App\Controller\Stations;

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
        $router = $request->getRouter();
        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsHlsStreams',
            id: 'station-hls-streams',
            title: __('HLS Streams'),
            props: [
                'listUrl' => $router->fromHere('api:stations:hls_streams'),
                'restartStatusUrl' => $router->fromHere('api:stations:restart-status'),
            ],
        );
    }
}
