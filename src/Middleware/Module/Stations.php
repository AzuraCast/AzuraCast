<?php
namespace App\Middleware\Module;

use App\Acl;
use App\Http\Request;
use App\Http\Response;
use App\Event;
use Azura\EventDispatcher;
use Slim\Route;

/**
 * Module middleware for the /station pages.
 */
class Stations
{
    /** @var Acl */
    protected $acl;

    /** @var EventDispatcher */
    protected $dispatcher;

    /**
     * @param Acl $acl
     * @param EventDispatcher $dispatcher
     *
     * @see \App\Provider\MiddlewareProvider
     */
    public function __construct(Acl $acl, EventDispatcher $dispatcher)
    {
        $this->acl = $acl;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        $view = $request->getView();

        $station = $request->getStation();
        $backend = $request->getStationBackend();
        $frontend = $request->getStationFrontend();

        $view->addData([
            'station'   => $station,
            'frontend'  => $frontend,
            'backend'   => $backend,
        ]);

        $event = new Event\BuildStationMenu($this->acl, $request->getUser(), $request->getRouter(), $station, $backend, $frontend);
        $this->dispatcher->dispatch(Event\BuildStationMenu::NAME, $event);

        $active_tab = null;
        $current_route = $request->getAttribute('route');
        if ($current_route instanceof Route) {
            $route_parts = explode(':', $current_route->getName());
            $active_tab = $route_parts[1];
        }

        $view->sidebar = $view->render('stations/sidebar', [
            'menu' => $event->getFilteredMenu(),
            'active' => $active_tab,
        ]);

        return $next($request, $response);
    }
}
