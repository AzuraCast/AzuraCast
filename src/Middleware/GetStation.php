<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Entity;
use App\Entity\Repository\StationRepository;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

/**
 * Retrieve the station specified in the request parameters, and throw an error if none exists but one is required.
 */
class GetStation implements MiddlewareInterface
{
    public function __construct(
        protected StationRepository $station_repo,
        protected Adapters $adapters
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route_args = RouteContext::fromRequest($request)->getRoute()?->getArguments();

        $id = $route_args['station_id'] ?? null;

        if (!empty($id)) {
            $record = $this->station_repo->findByIdentifier($id);

            if ($record instanceof Entity\Station) {
                $backend = $this->adapters->getBackendAdapter($record);
                $frontend = $this->adapters->getFrontendAdapter($record);
                $remotes = $this->adapters->getRemoteAdapters($record);

                $request = $request
                    ->withAttribute(ServerRequest::ATTR_STATION, $record)
                    ->withAttribute(ServerRequest::ATTR_STATION_BACKEND, $backend)
                    ->withAttribute(ServerRequest::ATTR_STATION_FRONTEND, $frontend)
                    ->withAttribute(ServerRequest::ATTR_STATION_REMOTES, $remotes);
            }
        }

        return $handler->handle($request);
    }
}
