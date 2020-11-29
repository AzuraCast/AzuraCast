<?php

namespace App\Controller\Stations;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class QueueController
{
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        return $request->getView()->renderToResponse($response, 'stations/queue/index');
    }
}
