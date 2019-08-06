<?php
namespace App\Middleware\Module;

use App\Acl;
use App\Event;
use App\Http\RequestHelper;
use Azura\EventDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteContext;

/**
 * Module middleware for the /admin pages.
 */
class Admin implements MiddlewareInterface
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
        $event = new Event\BuildAdminMenu($this->acl, RequestHelper::getUser($request), RequestHelper::getRouter($request));
        $this->dispatcher->dispatch($event);

        $view = RequestHelper::getView($request);

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
