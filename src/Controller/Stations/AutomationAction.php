<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class AutomationAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsAutomation',
            id: 'station-automation',
            title: __('Automated Assignment'),
            props: [
                'settingsUrl' => (string)$router->fromHere('api:stations:automation:settings'),
                'runUrl' => (string)$router->fromHere('api:stations:automation:run'),
            ],
        );
    }
}
