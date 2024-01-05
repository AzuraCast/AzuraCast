<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Apply a rate limit for requests on this page and throw an exception if the limit is exceeded.
 */
final class RateLimit extends AbstractMiddleware
{
    public function __construct(
        private readonly string $rlGroup = 'default',
        private readonly int $rlInterval = 5,
        private readonly int $rlLimit = 2
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $rateLimit = $request->getRateLimit();
        $rateLimit->checkRequestRateLimit($request, $this->rlGroup, $this->rlInterval, $this->rlLimit);

        return $handler->handle($request);
    }
}
