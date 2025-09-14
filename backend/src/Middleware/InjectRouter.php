<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\Router;
use App\Http\RouterInterface;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Set the current route on the URL object, and inject the URL object into the router.
 */
final class InjectRouter extends AbstractMiddleware
{
    public function __construct(
        private readonly RouterInterface $router
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Router $router */
        $router = $this->router->withRequest($request);

        Router::setInstance($router);

        $request = $request->withAttribute(ServerRequest::ATTR_ROUTER, $router);

        return $handler->handle($request);
    }
}
