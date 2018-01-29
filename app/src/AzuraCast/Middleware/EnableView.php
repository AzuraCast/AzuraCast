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

    /** @var array Default view values. */
    protected $view_defaults;

    public function __construct(View $view, array $view_defaults = [])
    {
        $this->view = $view;
        $this->view_defaults = $view_defaults;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        $this->view->addData($this->view_defaults);

        $request = $request->withAttribute('view', $this->view);

        $response = $next($request, $response);

        $this->view->reset();

        return $response;
    }
}