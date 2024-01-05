<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\NotLoggedInException;
use App\Http\ServerRequest;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Require that the user be logged in to view this page.
 */
final class RequireLogin extends AbstractMiddleware
{
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $request->getUser();
        } catch (Exception) {
            throw new NotLoggedInException();
        }

        return $handler->handle($request);
    }
}
