<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class FallbackAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Stations/Fallback',
            id: 'station-fallback',
            title: __('Custom Fallback File'),
            props: [
                'recordHasFallback' => !empty($station->getFallbackPath()),
            ],
        );
    }
}
