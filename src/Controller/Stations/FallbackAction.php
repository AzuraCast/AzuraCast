<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class FallbackAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $router = $request->getRouter();

        $station = $request->getStation();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsFallback',
            id: 'station-fallback',
            title: __('Custom Fallback File'),
            props: [
                'apiUrl' => $router->fromHere('api:stations:fallback'),
                'recordHasFallback' => !empty($station->getFallbackPath()),
            ],
        );
    }
}
