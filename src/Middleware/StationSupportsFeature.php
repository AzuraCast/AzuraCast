<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Enums\StationFeatures;
use App\Exception;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class StationSupportsFeature extends AbstractMiddleware
{
    public function __construct(
        private readonly StationFeatures $feature
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->feature->supportedForStation($request->getStation())) {
            throw new Exception\StationUnsupportedException();
        }

        return $handler->handle($request);
    }
}
