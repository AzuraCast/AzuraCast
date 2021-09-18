<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class QueueAction
{
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $router = $request->getRouter();
        $station = $request->getStation();

        return $request->getView()->renderToResponse(
            $response,
            'system/vue',
            [
                'title' => __('Upcoming Song Queue'),
                'id' => 'station-queue',
                'component' => 'Vue_StationsQueue',
                'props' => [
                    'listUrl' => (string)$router->fromHere('api:stations:queue'),
                    'clearUrl' => (string)$router->fromHere('api:stations:queue:clear'),
                    'stationTimeZone' => $station->getTimezone(),
                ],
            ]
        );
    }
}
