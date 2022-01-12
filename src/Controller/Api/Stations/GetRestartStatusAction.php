<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class GetRestartStatusAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();
        return $response->withJson([
            'has_started'   => $station->getHasStarted(),
            'needs_restart' => $station->getNeedsRestart(),
        ]);
    }
}
