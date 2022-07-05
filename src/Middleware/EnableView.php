<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\ServerRequest;
use App\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Inject the view object into the request and prepare it for rendering templates.
 */
final class EnableView implements MiddlewareInterface
{
    public function __construct(
        private readonly View $view
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $view = $this->view->withRequest($request);

        $request = $request->withAttribute(ServerRequest::ATTR_VIEW, $view);
        return $handler->handle($request);
    }
}
