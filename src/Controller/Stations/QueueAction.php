<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class QueueAction
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
            component: 'Vue_StationsQueue',
            id: 'station-queue',
            title: __('Upcoming Song Queue'),
            props: [
                'listUrl' => $router->fromHere('api:stations:queue'),
                'clearUrl' => $router->fromHere('api:stations:queue:clear'),
                'stationTimeZone' => $station->getTimezone(),
            ],
        );
    }
}
