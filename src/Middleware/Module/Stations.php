<?php

namespace App\Middleware\Module;

use App\Environment;
use App\Event;
use App\EventDispatcher;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteContext;

/**
 * Module middleware for the /station pages.
 */
class Stations
{
    protected EventDispatcher $dispatcher;

    protected Environment $environment;

    public function __construct(EventDispatcher $dispatcher, Environment $environment)
    {
        $this->dispatcher = $dispatcher;
        $this->environment = $environment;
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $view = $request->getView();

        $station = $request->getStation();
        $backend = $request->getStationBackend();
        $frontend = $request->getStationFrontend();

        $view->addData(
            [
                'station' => $station,
                'frontend' => $frontend,
                'backend' => $backend,
            ]
        );

        $event = new Event\BuildStationMenu($request, $this->environment, $station);
        $this->dispatcher->dispatch($event);

        $active_tab = null;
        $routeContext = RouteContext::fromRequest($request);
        $current_route = $routeContext->getRoute();
        if ($current_route instanceof RouteInterface) {
            $route_parts = explode(':', $current_route->getName());
            $active_tab = $route_parts[1];
        }

        $view->addData(
            [
                'sidebar' => $view->render(
                    'stations/sidebar',
                    [
                        'menu' => $event->getFilteredMenu(),
                        'active' => $active_tab,
                    ]
                ),
            ]
        );

        return $handler->handle($request);
    }
}
