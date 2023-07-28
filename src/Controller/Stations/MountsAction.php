<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class MountsAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();
        $station = $request->getStation();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Stations/Mounts',
            id: 'station-mounts',
            title: __('Mount Points'),
            props: [
                'listUrl' => $router->fromHere('api:stations:mounts'),
                'newIntroUrl' => $router->fromHere('api:stations:mounts:new-intro'),
                'restartStatusUrl' => $router->fromHere('api:stations:restart-status'),
                'stationFrontendType' => $station->getFrontendType()->value,
            ],
        );
    }
}
