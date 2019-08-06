<?php
namespace App\Middleware;

use App\Entity;
use App\Entity\Repository\StationRepository;
use App\Http\RequestHelper;
use App\Radio\Adapters;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

/**
 * Retrieve the station specified in the request parameters, and throw an error if none exists but one is required.
 */
class GetStation implements MiddlewareInterface
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
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeContext = RouteContext::fromRequest($request);
        $route_args = $routeContext->getRoute()->getArguments();

        $id = $route_args['station'] ?? null;

        if (!empty($id)) {
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
            }
        }

        return $handler->handle($request);
    }
}
