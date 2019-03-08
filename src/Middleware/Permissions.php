<?php
namespace App\Middleware;

use App\Exception\PermissionDenied;
use App\Http\Request;
use App\Http\Response;
use App\Acl;
use App\Entity;

/**
 * Get the current user entity object and assign it into the request if it exists.
 */
class Permissions
{
    /** @var Acl */
    protected $acl;

    public function __construct(Acl $acl)
    {
        $this->acl = $acl;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     * @throws \App\Exception\PermissionDenied
     */
    public function __invoke(Request $request, Response $response, $next, $action, $use_station = false): Response
    {
        if ($use_station) {
            $station = $request->getStation();
            $station_id = $station->getId();
        } else {
            $station_id = null;
        }

        try {
            try {
                $user = $request->getUser();
            } catch (\Exception $e) {
                throw new PermissionDenied;
            }

            if (!$this->acl->userAllowed($user, $action, $station_id)) {
                throw new PermissionDenied;
            }
        } catch (PermissionDenied $e) {
            if ($request->isApiCall()) {
                return $response->withStatus(403)
                    ->withJson(new Entity\Api\Error(403, $e->getMessage(), $e->getFormattedMessage()));
            }

            throw $e;
        }

        return $next($request, $response);
    }
}
