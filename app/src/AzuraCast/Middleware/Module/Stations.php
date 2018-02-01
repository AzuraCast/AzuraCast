<?php
namespace AzuraCast\Middleware\Module;

use App\Mvc\View;
use App\Session;
use AzuraCast\Acl\StationAcl;
use Slim\Http\Request;
use Slim\Http\Response;

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
        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        $view->addData([
            'station' => $request->getAttribute('station'),
            'frontend' => $request->getAttribute('station_frontend'),
            'backend' => $request->getAttribute('station_backend'),
        ]);

        $view->sidebar = $view->render('stations/sidebar');

        return $next($request, $response);
    }
}