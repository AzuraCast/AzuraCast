<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class PerformanceAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderToResponse(
            $response,
            'system/vue',
            [
                'title' => __('Song Listener Impact'),
                'id' => 'station-report-performance',
                'component' => 'Vue_StationsReportsPerformance',
                'props' => [
                    'apiUrl' => (string)$router->fromHere('api:stations:reports:performance'),
                ],
            ]
        );
    }
}
