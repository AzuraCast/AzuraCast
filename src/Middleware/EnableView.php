<?php
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
class EnableView implements MiddlewareInterface
{
    /** @var View */
    protected $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->view->addData([
            'request' => $request,
            'flash' => $request->getAttribute(ServerRequest::ATTR_SESSION_FLASH),
        ]);

        $request = $request->withAttribute(ServerRequest::ATTR_VIEW, $this->view);
        return $handler->handle($request);
    }
}
