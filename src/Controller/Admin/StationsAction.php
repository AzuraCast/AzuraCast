<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use App\VueComponent\StationFormComponent;
use Psr\Http\Message\ResponseInterface;

class StationsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        StationFormComponent $stationFormComponent
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminStations',
            id: 'admin-stations',
            title: __('Stations'),
            props: array_merge(
                $stationFormComponent->getProps($request),
                [
                    'listUrl' => (string)$router->fromHere('api:admin:stations'),
                ]
            )
        );
    }
}
