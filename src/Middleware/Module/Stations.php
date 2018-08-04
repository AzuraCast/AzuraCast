<?php
namespace App\Middleware\Module;

use App\Http\Request;
use App\Http\Response;

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

        $view->sidebar = $view->render('stations/sidebar');

        return $next($request, $response);
    }
}
