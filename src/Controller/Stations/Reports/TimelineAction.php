<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class TimelineAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Stations/Reports/Timeline',
            id: 'station-report-timeline',
            title: __('Song Playback Timeline'),
            props: [
                'baseApiUrl' => $router->fromHere('api:stations:history'),
            ]
        );
    }
}
