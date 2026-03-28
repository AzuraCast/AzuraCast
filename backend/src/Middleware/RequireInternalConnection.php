<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\Http\PermissionDeniedException;
use App\Http\ServerRequest;
use App\Utilities\Types;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Require that the page be viewed from the internal (in Docker, :6010) connection.
 */
class RequireInternalConnection extends AbstractMiddleware
{
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isInternal = Types::bool($request->getServerParam('IS_INTERNAL', false), false, true);

        if (!$isInternal) {
            throw PermissionDeniedException::create($request);
        }

        return $handler->handle($request);
    }
}
