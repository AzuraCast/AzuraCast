<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\ServerRequest;
use App\Utilities\Types;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Apply the "X-Forwarded-*" headers if they exist.
 */
final class ApplyXForwarded extends AbstractMiddleware
{
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();

        $hasXForwardedHeader = false;

        if ($request->hasHeader('X-Forwarded-For')) {
            $hasXForwardedHeader = true;
        }

        if ($request->hasHeader('X-Forwarded-Proto')) {
            $hasXForwardedHeader = true;
            $xfProto = Types::stringOrNull($request->getHeaderLine('X-Forwarded-Proto'), true);
            if (null !== $xfProto) {
                $uri = $uri->withScheme($xfProto);
            }
        }

        if ($request->hasHeader('X-Forwarded-Host')) {
            $hasXForwardedHeader = true;
            $xfHost = Types::stringOrNull($request->getHeaderLine('X-Forwarded-Host'), true);
            if (null !== $xfHost) {
                $uri = $uri->withHost($xfHost);
            }
        }

        if ($request->hasHeader('X-Forwarded-Port')) {
            $xfPort = Types::intOrNull($request->getHeaderLine('X-Forwarded-Port'));
            if (null !== $xfPort) {
                $uri = $uri->withPort($xfPort);
            }
        } elseif ($hasXForwardedHeader) {
            // A vast majority of reverse proxies will be proxying to the default web ports, so
            // if *any* X-Forwarded-* value is set, unset the port in the request.
            $uri = $uri->withPort(null);
        }

        $request = $request->withUri($uri);

        return $handler->handle($request);
    }
}
