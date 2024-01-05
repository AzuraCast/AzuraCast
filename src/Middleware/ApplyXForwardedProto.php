<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Apply the "X-Forwarded-Proto" header if it exists.
 */
final class ApplyXForwardedProto extends AbstractMiddleware
{
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->hasHeader('X-Forwarded-Proto')) {
            $uri = $request->getUri();
            $uri = $uri->withScheme($request->getHeaderLine('X-Forwarded-Proto'));
            $request = $request->withUri($uri);
        }

        return $handler->handle($request);
    }
}
