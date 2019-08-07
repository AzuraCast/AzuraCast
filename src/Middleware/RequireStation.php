<?php
namespace App\Middleware;

use App\Entity;
use App\Exception\StationNotFound;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Require that the user be logged in to view this page.
 */
class RequireStation implements MiddlewareInterface
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
            RequestHelper::getAcl($request);
        }
        catch(\Exception $e)
        {
            $e = new StationNotFound;
            if (RequestHelper::isApiCall($request)) {
                return ResponseHelper::withJson(
                    new \Slim\Psr7\Response(404),
                    new Entity\Api\Error(404, $e->getMessage(), $e->getFormattedMessage())
                );
            }

            throw $e;
        }

        return $handler->handle($request);
    }
}
