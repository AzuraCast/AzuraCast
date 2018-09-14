<?php
namespace App\Controller\Stations\Reports;

use App\Http\Request;
use App\Http\Response;

class TimelineController
{
    public function __invoke(Request $request, Response $response): Response
    {
        return $request->getView()->renderToResponse($response, 'stations/reports/timeline');
    }
}
