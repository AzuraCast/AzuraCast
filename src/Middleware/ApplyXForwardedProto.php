<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Apply the "X-Forwarded-Proto" header if it exists.
 */
final class ApplyXForwardedProto implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->hasHeader('X-Forwarded-Proto')) {
            $uri = $request->getUri();
            $uri = $uri->withScheme($request->getHeaderLine('X-Forwarded-Proto'));
            $request = $request->withUri($uri);
        }

        return $handler->handle($request);
    }
}
