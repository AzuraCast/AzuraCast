<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ListenersAction
{
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        return $request->getView()->renderToResponse(
            $response,
            'stations/reports/listeners',
        );
    }
}
