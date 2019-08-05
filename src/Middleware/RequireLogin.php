<?php
namespace App\Middleware;

use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Require that the user be logged in to view this page.
 */
class RequireLogin implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try
        {
            RequestHelper::getUser($request);
        }
        catch(\Exception $e)
        {
            throw new \App\Exception\NotLoggedIn;
        }

        $response = $handler->handle($request);
        $response = ResponseHelper::withNoCache($response);

        return $response;
    }
}
