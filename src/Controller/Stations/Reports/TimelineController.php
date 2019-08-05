<?php
namespace App\Controller\Stations\Reports;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseInterface;

class TimelineController
{
    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        return $request->getView()->renderToResponse($response, 'stations/reports/timeline');
    }
}
