<?php
namespace AzuraCast\Middleware;

use App\Auth;
use App\Mvc\View;
use AzuraCast\Assets;
use AzuraCast\Customization;
use Doctrine\ORM\EntityManager;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

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
        $request = $request->withAttribute('view', $this->view);

        $response = $next($request, $response);

        return $response;
    }
}