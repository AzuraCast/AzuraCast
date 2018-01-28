<?php
namespace AzuraCast\Middleware;

use Entity;
use Entity\Repository\StationRepository;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Retrieve the station specified in the request parameters, and throw an error if none exists but one is required.
 */
class GetStation
{
    /** @var StationRepository */
    protected $station_repo;

    public function __construct(StationRepository $station_repo)
    {
        $this->station_repo = $station_repo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @param bool $station_required
     * @param string $station_param
     * @return Response
     * @throws \Exception
     */
    public function __invoke(Request $request, Response $response, $next, $station_required = true, $station_param = 'station'): Response
    {
        $route_args = $request->getAttribute('routeInfo')[2];
        $id = $route_args[$station_param] ?? null;

        if (empty($id) && $station_required) {
            throw new \RuntimeException('Station not found!');
        }

        if (is_numeric($id)) {
            $record = $this->station_repo->find($id);
        } else {
            $record = $this->station_repo->findByShortCode($id);
        }

        if ($record instanceof Entity\Station) {
            $request = $request->withAttribute('station', $record);
        } else if ($station_required) {
            throw new \RuntimeException('Station not found!');
        }

        return $next($request, $response);
    }
}