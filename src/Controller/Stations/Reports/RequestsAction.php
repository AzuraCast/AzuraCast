<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class RequestsAction
{
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $router = $request->getRouter();
        $station = $request->getStation();

        return $request->getView()->renderToResponse(
            $response,
            'system/vue',
            [
                'title' => __('Song Requests'),
                'id' => 'station-report-requests',
                'component' => 'Vue_StationsReportsRequests',
                'props' => [
                    'listUrl' => (string)$router->fromHere('api:stations:reports:requests'),
                    'clearUrl' => (string)$router->fromHere('api:stations:reports:requests:clear'),
                    'stationTimeZone' => $station->getTimezone(),
                ],
            ]
        );
    }
}
