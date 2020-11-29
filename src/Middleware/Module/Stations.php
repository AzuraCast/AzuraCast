<?php

namespace App\Middleware\Module;

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

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $view = $request->getView();

        $station = $request->getStation();
        $backend = $request->getStationBackend();
        $frontend = $request->getStationFrontend();

        $view->addData([
            'station' => $station,
            'frontend' => $frontend,
            'backend' => $backend,
        ]);

        $user = $request->getUser();
        $router = $request->getRouter();

        $event = new Event\BuildStationMenu($request->getAcl(), $user, $router, $station, $backend, $frontend);
        $this->dispatcher->dispatch($event);

        $active_tab = null;
        $routeContext = RouteContext::fromRequest($request);
        $current_route = $routeContext->getRoute();
        if ($current_route instanceof RouteInterface) {
            $route_parts = explode(':', $current_route->getName());
            $active_tab = $route_parts[1];
        }

        $view->addData([
            'sidebar' => $view->render('stations/sidebar', [
                'menu' => $event->getFilteredMenu(),
                'active' => $active_tab,
            ]),
        ]);

        return $handler->handle($request);
    }
}
