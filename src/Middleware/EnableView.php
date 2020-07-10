<?php
namespace App\Middleware;

use App\Http\ServerRequest;
use App\ViewFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Inject the view object into the request and prepare it for rendering templates.
 */
class EnableView implements MiddlewareInterface
{
    protected ViewFactory $viewFactory;

    public function __construct(ViewFactory $viewFactory)
    {
        $this->viewFactory = $viewFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $view = $this->viewFactory->create($request);

        $request = $request->withAttribute(ServerRequest::ATTR_VIEW, $view);
        return $handler->handle($request);
    }
}
