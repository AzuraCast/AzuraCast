<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\Http\NotLoggedInException;
use App\Exception\NotFoundException;
use App\Http\ServerRequest;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Require that the user be logged in to view non-public station page.
 */
final class RequireLoginNonPublicStation extends RequireLogin
{
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $request->getStation();
            if (!$request->getStation()->enable_public_page) {
                return parent::__invoke($request, $handler);
            }
        } catch (Exception) {
            throw NotFoundException::station();
        }

        return $handler->handle($request);
    }
}
