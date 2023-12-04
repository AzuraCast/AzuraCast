<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\ServerRequest;
use App\RateLimit;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Inject core services into the request object for use further down the stack.
 */
final class InjectRateLimit extends AbstractMiddleware
{
    public function __construct(
        private readonly RateLimit $rateLimit
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute(ServerRequest::ATTR_RATE_LIMIT, $this->rateLimit);

        return $handler->handle($request);
    }
}
