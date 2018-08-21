<?php
namespace App\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use Slim\Route;

/**
 * Set the current route on the URL object, and inject the URL object into the
 */
class EnableRouter
{
    /** @var Router */
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        $route = $request->getAttribute('route');

        if ($route instanceof Route) {
            $this->router->setCurrentRoute($route, $request->getQueryParams());
        }

        $request = $request->withAttribute(Request::ATTRIBUTE_ROUTER, $this->router);

        return $next($request, $response);
    }
}
