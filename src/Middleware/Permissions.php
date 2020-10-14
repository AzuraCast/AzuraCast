<?php

namespace App\Middleware;

use App\Exception\PermissionDeniedException;
use App\Http\ServerRequest;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Get the current user entity object and assign it into the request if it exists.
 */
class Permissions
{
    protected string $action;

    protected bool $use_station;

    public function __construct(
        string $action,
        bool $use_station = false
    ) {
        $this->action = $action;
        $this->use_station = $use_station;
    }

    /**
     * @param ServerRequest $request
     * @param RequestHandlerInterface $handler
     */
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->use_station) {
            $station = $request->getStation();
            $station_id = $station->getId();
        } else {
            $station_id = null;
        }

        try {
            $user = $request->getUser();
        } catch (Exception $e) {
            throw new PermissionDeniedException();
        }

        $acl = $request->getAcl();
        if (!$acl->userAllowed($user, $this->action, $station_id)) {
            throw new PermissionDeniedException();
        }

        return $handler->handle($request);
    }
}
