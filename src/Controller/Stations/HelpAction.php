<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class HelpAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsLogs',
            id: 'stations-logs',
            title: __('Logs'),
            props: [
                'logsUrl' => (string)$router->fromHere('api:stations:logs'),
            ],
        );
    }
}
