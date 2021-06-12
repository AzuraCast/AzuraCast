<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class MountsAction
{
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        return $request->getView()->renderToResponse(
            $response,
            'stations/mounts/index',
            [
                'station' => $station,
            ]
        );
    }
}
