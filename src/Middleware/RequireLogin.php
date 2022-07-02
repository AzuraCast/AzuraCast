<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\NotLoggedInException;
use App\Http\Response;
use App\Http\ServerRequest;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Require that the user be logged in to view this page.
 */
final class RequireLogin
{
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $request->getUser();
        } catch (Exception) {
            throw new NotLoggedInException();
        }

        $response = $handler->handle($request);

        if ($response instanceof Response) {
            $response = $response->withNoCache();
        }

        return $response;
    }
}
