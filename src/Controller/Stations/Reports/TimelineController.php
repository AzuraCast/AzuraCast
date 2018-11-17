<?php
namespace App\Controller\Stations\Reports;

use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class TimelineController
{
    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        return $request->getView()->renderToResponse($response, 'stations/reports/timeline');
    }
}
