<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Entity\Repository\StationRepository;
use App\Entity\Station;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

/**
 * Retrieve the station specified in the request parameters, and throw an error if none exists but one is required.
 */
final class GetStation implements MiddlewareInterface
{
    public function __construct(
        private readonly StationRepository $stationRepo
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeArgs = RouteContext::fromRequest($request)->getRoute()?->getArguments();

        $id = $routeArgs['station_id'] ?? null;

        if (!empty($id)) {
            $record = $this->stationRepo->findByIdentifier($id);

            if ($record instanceof Station) {
                $request = $request->withAttribute(ServerRequest::ATTR_STATION, $record);
            }
        }

        return $handler->handle($request);
    }
}
