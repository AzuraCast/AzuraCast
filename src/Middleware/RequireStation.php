<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\StationNotFoundException;
use App\Http\ServerRequest;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Require that the user be logged in to view this page.
 */
final class RequireStation extends AbstractMiddleware
{
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $request->getStation();
        } catch (Exception) {
            throw new StationNotFoundException();
        }

        return $handler->handle($request);
    }
}
