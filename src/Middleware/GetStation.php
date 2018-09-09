<?php
namespace App\Middleware;

use App\Radio\Adapters;
use App\Entity;
use App\Entity\Repository\StationRepository;
use App\Http\Request;
use App\Http\Response;

/**
 * Retrieve the station specified in the request parameters, and throw an error if none exists but one is required.
 */
class GetStation
{
    /** @var StationRepository */
    protected $station_repo;

    /** @var Adapters */
    protected $adapters;

    public function __construct(StationRepository $station_repo, Adapters $adapters)
    {
        $this->station_repo = $station_repo;
        $this->adapters = $adapters;
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
            $frontend = $this->adapters->getFrontendAdapter($record);
            $backend = $this->adapters->getBackendAdapter($record);
            $remotes = $this->adapters->getRemoteAdapters($record);

            $request = $request
                ->withAttribute(Request::ATTRIBUTE_STATION, $record)
                ->withAttribute(Request::ATTRIBUTE_STATION_FRONTEND, $frontend)
                ->withAttribute(Request::ATTRIBUTE_STATION_BACKEND, $backend);
        } else if ($station_required) {
            throw new \RuntimeException('Station not found!');
        }

        return $next($request, $response);
    }
}
