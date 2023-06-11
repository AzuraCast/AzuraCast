<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ListenersAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsReportsListeners',
            id: 'station-report-listeners',
            title: __('Listeners'),
            props: [
                'apiUrl' => $router->fromHere('api:listeners:index'),
                'stationTz' => $station->getTimezone(),
            ]
        );
    }
}
