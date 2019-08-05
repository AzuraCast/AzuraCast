<?php
namespace App\Middleware\Module;

use App\Acl;
use App\Http\RequestHelper;
use App\Event;
use Azura\EventDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteContext;

/**
 * Module middleware for the /station pages.
 */
class Stations implements MiddlewareInterface
{
    /** @var Acl */
    protected $acl;

    /** @var EventDispatcher */
    protected $dispatcher;

    /**
     * @param Acl $acl
     * @param EventDispatcher $dispatcher
     */
    public function __construct(Acl $acl, EventDispatcher $dispatcher)
    {
        $this->acl = $acl;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $view = RequestHelper::getView($request);

        $station = RequestHelper::getStation($request);
        $backend = RequestHelper::getStationBackend($request);
        $frontend = RequestHelper::getStationFrontend($request);

        date_default_timezone_set($station->getTimezone());

        $view->addData([
            'station'   => $station,
            'frontend'  => $frontend,
            'backend'   => $backend,
        ]);

        $user = RequestHelper::getUser($request);
        $router = RequestHelper::getRouter($request);

        $event = new Event\BuildStationMenu($this->acl, $user, $router, $station, $backend, $frontend);
        $this->dispatcher->dispatch(Event\BuildStationMenu::NAME, $event);

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
