<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Vue\Reports;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\IpGeolocation;
use Psr\Http\Message\ResponseInterface;

final class ListenersAction implements SingleActionInterface
{
    public function __construct(
        private readonly IpGeolocation $ipGeolocation
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $response->withJson([
            'attribution' => $this->ipGeolocation->getAttribution(),
        ]);
    }
}
