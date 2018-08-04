<?php
namespace App\Middleware;

use App\Session;
use App\Http\Request;
use App\Http\Response;

/**
 * Inject the view object into the request and prepare it for rendering templates.
 */
class EnableSession
{
    /** @var Session */
    protected $session;

    /**
     * EnableSession constructor.
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        $request = $request->withAttribute(Request::ATTRIBUTE_SESSION, $this->session);

        return $next($request, $response);
    }
}
