<?php
namespace App\Controller\Stations;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseInterface;

class QueueController
{
    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        return $request->getView()->renderToResponse($response, 'stations/queue/index');
    }
}
