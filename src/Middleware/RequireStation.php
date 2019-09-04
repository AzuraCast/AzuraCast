<?php
namespace App\Middleware;

use App\Exception\StationNotFound;
use App\Http\ServerRequest;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Require that the user be logged in to view this page.
 */
class RequireStation
{
    /**
     * @param ServerRequest $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $request->getStation();
        } catch (Exception $e) {
            throw new StationNotFound;
        }

        return $handler->handle($request);
    }
}
