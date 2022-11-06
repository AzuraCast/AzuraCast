<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use App\VueComponent\StationFormComponent;
use Psr\Http\Message\ResponseInterface;

final class StationsAction
{
    public function __construct(
        private readonly StationFormComponent $stationFormComponent,
        private readonly Adapters $adapters
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminStations',
            id: 'admin-stations',
            title: __('Stations'),
            props: array_merge(
                $this->stationFormComponent->getProps($request),
                [
                    'listUrl' => $router->fromHere('api:admin:stations'),
                    'frontendTypes' => $this->adapters->listFrontendAdapters(),
                    'backendTypes' => $this->adapters->listBackendAdapters(),
                ]
            )
        );
    }
}
