<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PerformanceAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        int|string $station_id
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsReportsPerformance',
            id: 'station-report-performance',
            title: __('Song Listener Impact'),
            props: [
                'apiUrl' => (string)$router->fromHere('api:stations:reports:performance'),
            ]
        );
    }
}
