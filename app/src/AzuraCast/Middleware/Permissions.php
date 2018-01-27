<?php
namespace AzuraCast\Middleware;

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
    public function __invoke(Request $request, Response $response, $next, $action, $station_param = null): Response
    {
        if (!empty($station_param)) {
            $station_id = $request->getParam($station_param);
        } else {
            $station_id = null;
        }

        if (!$this->acl->isAllowed($action, $station_id)) {
            throw new \App\Exception\PermissionDenied;
        }

        return $next($request, $response);
    }
}