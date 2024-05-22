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

        if ($request->hasHeader('X-Forwarded-Proto')) {
            $xfProto = Types::stringOrNull($request->getHeaderLine('X-Forwarded-Proto'), true);
            if (null !== $xfProto) {
                $uri = $uri->withScheme($xfProto);
            }
        }

        if ($request->hasHeader('X-Forwarded-Host')) {
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
        }

        $request = $request->withUri($uri);

        return $handler->handle($request);
    }
}
