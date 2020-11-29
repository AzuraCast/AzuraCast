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
    protected string $rl_group;

    protected int $rl_timeout;

    protected int $rl_interval;

    public function __construct(
        string $rl_group = 'default',
        int $rl_timeout = 5,
        int $rl_interval = 2
    ) {
        $this->rl_group = $rl_group;
        $this->rl_timeout = $rl_timeout;
        $this->rl_interval = $rl_interval;
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $rateLimit = $request->getRateLimit();
        $rateLimit->checkRateLimit($request, $this->rl_group, $this->rl_timeout, $this->rl_interval);

        return $handler->handle($request);
    }
}
