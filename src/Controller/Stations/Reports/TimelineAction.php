<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class TimelineAction
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
            component: 'Vue_StationsReportsTimeline',
            id: 'station-report-timeline',
            title: __('Song Playback Timeline'),
            props: [
                'baseApiUrl' => $router->fromHere('api:stations:history'),
                'stationTimeZone' => $station->getTimezone(),
            ]
        );
    }
}
