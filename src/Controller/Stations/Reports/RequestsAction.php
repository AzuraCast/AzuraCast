<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class RequestsAction
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
            component: 'Vue_StationsReportsRequests',
            id: 'station-report-requests',
            title: __('Song Requests'),
            props: [
                'listUrl' => $router->fromHere('api:stations:reports:requests'),
                'clearUrl' => $router->fromHere('api:stations:reports:requests:clear'),
                'stationTimeZone' => $station->getTimezone(),
            ]
        );
    }
}
