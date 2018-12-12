<?php
namespace App\Controller\Stations;

use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class QueueController
{
    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        return $request->getView()->renderToResponse($response, 'stations/queue/index');
    }
}
