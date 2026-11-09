<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Enums\StationPermissions;
use App\Exception\Http\PermissionDeniedException;
use App\Exception\NotFoundException;
use App\Http\ServerRequest;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Require that the user be logged in to view non-public station API.
 */
final class StationPublicApi extends AbstractMiddleware
{
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $station = $request->getStation();

            if (!$station->enable_public_api) {
                // Require login.
                $request->getUser();

                if (
                    !$request->getAcl()->isAllowed(
                        StationPermissions::View,
                        $station
                    )
                ) {
                    throw PermissionDeniedException::create($request);
                }
            }
        } catch (Exception) {
            throw NotFoundException::station();
        }

        return $handler->handle($request);
    }
}
