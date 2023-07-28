<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class RequestsAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Stations/Reports/Requests',
            id: 'station-report-requests',
            title: __('Song Requests'),
            props: [
                'listUrl' => $router->fromHere('api:stations:reports:requests'),
                'clearUrl' => $router->fromHere('api:stations:reports:requests:clear'),
            ]
        );
    }
}
