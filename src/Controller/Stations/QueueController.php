<?php
namespace App\Controller\Stations;

use App\Http\RequestHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class QueueController
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return RequestHelper::getView($request)->renderToResponse($response, 'stations/queue/index');
    }
}
