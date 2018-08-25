<?php
namespace App\Middleware\Module;

use App\Http\Request;
use App\Http\Response;
use Slim\Route;

/**
 * Module middleware for the /station pages.
 */
class Stations
{
    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        $view = $request->getView();

        $view->addData([
            'station'   => $request->getStation(),
            'frontend'  => $request->getStationFrontend(),
            'backend'   => $request->getStationBackend(),
        ]);

        $active_tab = null;
        $current_route = $request->getAttribute('route');
        if ($current_route instanceof Route) {
            $route_parts = explode(':', $current_route->getName());
            $active_tab = $route_parts[1];
        }

        $view->sidebar = $view->render('stations/sidebar', [
            'active_tab' => $active_tab,
        ]);

        return $next($request, $response);
    }
}
