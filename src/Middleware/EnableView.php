<?php
namespace App\Middleware;

use App\Mvc\View;
use App\Http\Request;
use App\Http\Response;

/**
 * Inject the view object into the request and prepare it for rendering templates.
 */
class EnableView
{
    /** @var View */
    protected $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        $this->view->addData([
            'user' => $request->getAttribute(Request::ATTRIBUTE_USER),
        ]);

        $request = $request->withAttribute(Request::ATTRIBUTE_VIEW, $this->view);

        $response = $next($request, $response);

        return $response;
    }
}
