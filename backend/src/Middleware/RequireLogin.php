<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\Http\NotLoggedInException;
use App\Http\ServerRequest;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Require that the user be logged in to view this page.
 */
class RequireLogin extends AbstractMiddleware
{
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $request->getUser();
        } catch (Exception) {
            throw NotLoggedInException::create($request);
        }

        return $handler->handle($request);
    }
}
