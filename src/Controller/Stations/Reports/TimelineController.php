<?php
namespace App\Controller\Stations\Reports;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TimelineController
{
    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'stations/reports/timeline');
    }
}
