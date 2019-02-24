<?php
namespace App\Middleware\Module;

use App\Acl;
use App\Http\Request;
use App\Http\Response;
use Azura\EventDispatcher;
use App\Event;
use Slim\Route;

/**
 * Module middleware for the /admin pages.
 */
class Admin
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
        $event = new Event\BuildAdminMenu($this->acl, $request->getUser(), $request->getRouter());
        $this->dispatcher->dispatch(Event\BuildAdminMenu::NAME, $event);

        $view = $request->getView();

        $active_tab = null;
        $current_route = $request->getAttribute('route');
        if ($current_route instanceof Route) {
            $route_parts = explode(':', $current_route->getName());
            $active_tab = $route_parts[1];
        }

        $view->admin_panels = $event->getFilteredMenu();
        $view->sidebar = $view->render('admin/sidebar', [
            'active_tab' => $active_tab,
        ]);

        return $next($request, $response);
    }
}
