<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Entity;
use App\Entity\Repository\StationRepository;
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
        private readonly StationRepository $station_repo
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route_args = RouteContext::fromRequest($request)->getRoute()?->getArguments();

        $id = $route_args['station_id'] ?? null;

        if (!empty($id)) {
            $record = $this->station_repo->findByIdentifier($id);

            if ($record instanceof Entity\Station) {
                $request = $request->withAttribute(ServerRequest::ATTR_STATION, $record);
            }
        }

        return $handler->handle($request);
    }
}
