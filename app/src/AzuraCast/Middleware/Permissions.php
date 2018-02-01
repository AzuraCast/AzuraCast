<?php
namespace AzuraCast\Middleware;

use Entity\Station;
use Slim\Http\Request;
use Slim\Http\Response;

use AzuraCast\Acl\StationAcl;

/**
 * Get the current user entity object and assign it into the request if it exists.
 */
class Permissions
{
    /** @var StationAcl */
    protected $acl;

    public function __construct(StationAcl $acl)
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
            /** @var Station $station */
            $station = $request->getAttribute('station');
            $station_id = $station->getId();
        } else {
            $station_id = null;
        }

        if (!$this->acl->isAllowed($action, $station_id)) {
            throw new \App\Exception\PermissionDenied;
        }

        return $next($request, $response);
    }
}