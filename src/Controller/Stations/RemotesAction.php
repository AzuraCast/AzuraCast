<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class RemotesAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsRemotes',
            id: 'station-remotes',
            title: __('Remote Relays'),
            props: [
                'listUrl' => (string)$router->fromHere('api:stations:remotes'),
                'restartStatusUrl' => (string)$router->fromHere('api:stations:restart-status'),
            ],
        );
    }
}
