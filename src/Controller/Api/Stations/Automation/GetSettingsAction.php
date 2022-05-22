<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Automation;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetSettingsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        int|string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        return $response->withJson(
            (array)$station->getAutomationSettings()
        );
    }
}
