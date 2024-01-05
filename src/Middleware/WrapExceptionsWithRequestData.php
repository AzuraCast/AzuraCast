<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\WrappedException;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Wrap all exceptions thrown past this point with rich metadata.
 */
final class WrapExceptionsWithRequestData extends AbstractMiddleware
{
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            throw new WrappedException($request, $e);
        }
    }
}
