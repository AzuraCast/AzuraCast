<?php
namespace App\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Remove trailing slash from all URLs when routing.
 */
class RemoveSlashes
{
    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if ($path !== '/' && substr($path, -1) === '/') {
            // permanently redirect paths with a trailing slash
            // to their non-trailing counterpart
            $uri = $uri->withPath(substr($path, 0, -1));

            return $response->withRedirect((string)$uri, 301);
        }

        return $next($request, $response);
    }
}
