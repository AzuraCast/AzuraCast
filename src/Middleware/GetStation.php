<?php
namespace App\Middleware;

use App\Exception\StationNotFound;
use App\Http\RequestHelper;
use App\Radio\Adapters;
use App\Entity;
use App\Entity\Repository\StationRepository;
use App\Http\ResponseHelper;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

/**
 * Retrieve the station specified in the request parameters, and throw an error if none exists but one is required.
 */
class GetStation
{
    /** @var StationRepository */
    protected $station_repo;

    /** @var Adapters */
    protected $adapters;

    public function __construct(
        EntityManager $em,
        Adapters $adapters
    ) {
        $this->station_repo = $em->getRepository(Entity\Station::class);
        $this->adapters = $adapters;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @param bool $station_required
     * @param string $station_param
     *
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        bool $station_required = true,
        string $station_param = 'station'
    ): ResponseInterface {
        try {
            $routeContext = RouteContext::fromRequest($request);
            $routeContext->getRoute()->getArguments();

            $id = $route_args[$station_param] ?? null;

            if (empty($id) && $station_required) {
                throw new StationNotFound;
            }

            if (is_numeric($id)) {
                $record = $this->station_repo->find($id);
            } else {
                $record = $this->station_repo->findByShortCode($id);
            }

            if ($record instanceof Entity\Station) {
                $backend = $this->adapters->getBackendAdapter($record);
                $frontend = $this->adapters->getFrontendAdapter($record);
                $remotes = $this->adapters->getRemoteAdapters($record);

                $request = RequestHelper::injectStationComponents(
                    $request,
                    $record,
                    $backend,
                    $frontend,
                    $remotes
                );
            } else if ($station_required) {
                throw new StationNotFound;
            }
        } catch (StationNotFound $e) {
            if (RequestHelper::isApiCall($request)) {
                return ResponseHelper::withJson(
                    new \Slim\Psr7\Response(404),
                    new Entity\Api\Error(404, $e->getMessage(), $e->getFormattedMessage())
                );
            }

            throw $e;
        }

        return $handler->handle($request);
    }
}
