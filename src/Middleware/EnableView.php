<?php

namespace App\Middleware;

use App\Http\ServerRequest;
use App\View;
use DI\FactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Inject the view object into the request and prepare it for rendering templates.
 */
class EnableView implements MiddlewareInterface
{
    protected FactoryInterface $factory;

    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $view = $this->factory->make(
            View::class,
            [
                'request' => $request,
            ]
        );

        $request = $request->withAttribute(ServerRequest::ATTR_VIEW, $view);
        return $handler->handle($request);
    }
}
