<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Enums\PermissionInterface;
use App\Exception\Http\PermissionDeniedException;
use App\Http\ServerRequest;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Get the current user entity object and assign it into the request if it exists.
 */
final class Permissions extends AbstractMiddleware
{
    public function __construct(
        private readonly string|PermissionInterface $action,
        private readonly bool $useStation = false
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->useStation) {
            $stationId = $request->getStation()->getId();
        } else {
            $stationId = null;
        }

        try {
            $user = $request->getUser();
        } catch (Exception) {
            throw PermissionDeniedException::create($request);
        }

        $acl = $request->getAcl();
        if (!$acl->userAllowed($user, $this->action, $stationId)) {
            throw PermissionDeniedException::create($request);
        }

        return $handler->handle($request);
    }
}
