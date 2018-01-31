<?php
namespace AzuraCast\Middleware;

use App\Mvc\View;
use AzuraCast\Radio\Adapters;
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

            $request = $request
                ->withAttribute('station', $record)
                ->withAttribute('station_frontend', $frontend)
                ->withAttribute('station_backend', $backend);
        } else if ($station_required) {
            throw new \RuntimeException('Station not found!');
        }

        return $next($request, $response);
    }
}