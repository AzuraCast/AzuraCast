<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Vue;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PlaylistsAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        return $response->withJson([
            'useManualAutoDj' => $station->useManualAutoDJ(),
        ]);
    }
}
