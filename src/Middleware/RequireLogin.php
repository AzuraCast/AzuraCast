<?php
namespace App\Middleware;

use App\Http\Request;
use App\Http\Response;
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
        try
        {
            $request->getUser();
        }
        catch(\Exception $e)
        {
            throw new \App\Exception\NotLoggedIn;
        }

        return $next($request, $response);
    }
}
