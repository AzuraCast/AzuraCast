<?php

namespace App\Middleware;

use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Apply a rate limit for requests on this page and throw an exception if the limit is exceeded.
 */
class RateLimit
{
    public function __construct(
        protected string $rl_group = 'default',
        protected int $rl_timeout = 5,
        protected int $rl_interval = 2
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $rateLimit = $request->getRateLimit();
        $rateLimit->checkRequestRateLimit($request, $this->rl_group, $this->rl_timeout, $this->rl_interval);

        return $handler->handle($request);
    }
}
