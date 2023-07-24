<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class RestartAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $router = $request->getRouter();

        $frontendEnum = $station->getFrontendType();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Stations/Restart',
            id: 'stations-restart',
            title: __('Update Station Configuration'),
            props: [
                'canReload' => $frontendEnum->supportsReload(),
                'reloadUrl' => $router->fromHere('api:stations:reload'),
                'restartUrl' => $router->fromHere('api:stations:restart'),
                'redirectUrl' => $router->fromHere('stations:profile:index'),
            ]
        );
    }
}
