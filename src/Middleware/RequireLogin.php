<?php
namespace App\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;
use App\Entity;

/**
 * Require that the user be logged in to view this page.
 */
class RequireLogin
{
    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     * @throws \App\Exception\NotLoggedIn
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        $user = $request->getAttribute('user');

        if (!($user instanceof Entity\User)) {
            throw new \App\Exception\NotLoggedIn;
        }

        return $next($request, $response);
    }
}
