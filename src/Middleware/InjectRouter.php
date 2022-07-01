<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\RouterInterface;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Set the current route on the URL object, and inject the URL object into the router.
 */
final class InjectRouter implements MiddlewareInterface
{
    public function __construct(
        private readonly RouterInterface $router
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $router = $this->router->withRequest($request);

        $request = $request->withAttribute(ServerRequest::ATTR_ROUTER, $router);

        return $handler->handle($request);
    }
}
