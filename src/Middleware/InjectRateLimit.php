<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\ServerRequest;
use App\RateLimit;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Inject core services into the request object for use further down the stack.
 */
final class InjectRateLimit implements MiddlewareInterface
{
    public function __construct(
        private readonly RateLimit $rateLimit
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute(ServerRequest::ATTR_RATE_LIMIT, $this->rateLimit);

        return $handler->handle($request);
    }
}
