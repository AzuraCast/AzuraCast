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
 * Module middleware for the /admin pages.
 */
class Admin
{
    protected EventDispatcher $dispatcher;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $event = new Event\BuildAdminMenu($request->getAcl(), $request->getUser(), $request->getRouter());
        $this->dispatcher->dispatch($event);

        $view = $request->getView();

        $active_tab = null;
        $routeContext = RouteContext::fromRequest($request);
        $current_route = $routeContext->getRoute();

        if ($current_route instanceof RouteInterface) {
            $route_parts = explode(':', $current_route->getName());
            $active_tab = $route_parts[1];
        }

        $view->addData([
            'admin_panels' => $event->getFilteredMenu(),
        ]);

        // These two intentionally separated (the sidebar needs admin_panels).
        $view->addData([
            'sidebar' => $view->render('admin/sidebar', [
                'active_tab' => $active_tab,
            ]),
        ]);

        return $handler->handle($request);
    }
}
