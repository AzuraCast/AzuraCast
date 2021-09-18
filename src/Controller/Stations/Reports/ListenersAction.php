<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ListenersAction
{
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $router = $request->getRouter();

        return $request->getView()->renderToResponse(
            $response,
            'system/vue',
            [
                'title' => __('Listeners'),
                'id' => 'station-report-listeners',
                'component' => 'Vue_StationsReportsListeners',
                'props' => [
                    'apiUrl' => (string)$router->fromHere('api:listeners:index'),
                    'stationTz' => $station->getTimezone(),
                ],
            ]
        );
    }
}
