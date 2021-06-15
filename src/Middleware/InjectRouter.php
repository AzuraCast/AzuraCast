<?php

namespace App\Middleware;

use App\Http\Router;
use App\Http\ServerRequest;
use DI\FactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Set the current route on the URL object, and inject the URL object into the router.
 */
class InjectRouter implements MiddlewareInterface
{
    public function __construct(
        protected FactoryInterface $factory
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $router = $this->factory->make(
            Router::class,
            [
                'request' => $request,
            ]
        );

        $request = $request->withAttribute(ServerRequest::ATTR_ROUTER, $router);

        return $handler->handle($request);
    }
}
