<?php
namespace App\Controller\Stations\Reports;

use App\Http\RequestHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TimelineController
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return RequestHelper::getView($request)->renderToResponse($response, 'stations/reports/timeline');
    }
}
