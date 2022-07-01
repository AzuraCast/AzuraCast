<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\WrappedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Wrap all exceptions thrown past this point with rich metadata.
 */
final class WrapExceptionsWithRequestData implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            throw new WrappedException($request, $e);
        }
    }
}
