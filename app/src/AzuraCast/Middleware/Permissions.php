<?php
namespace AzuraCast\Middleware;

use App\Auth;
use AzuraCast\Assets;
use AzuraCast\Customization;
use Doctrine\ORM\EntityManager;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

use AzuraCast\Acl\StationAcl;

/**
 * Get the current user entity object and assign it into the request if it exists.
 */
class Permissions
{
    /** @var StationAcl */
    protected $acl;

    public function __construct(StationAcl $acl)
    {
        $this->acl = $acl;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     * @throws \App\Exception
     */
    public function __invoke(Request $request, Response $response, $next, $permissions): Response
    {


        $user = ($this->auth->isLoggedIn()) ? $this->auth->getLoggedInUser() : null;

        // Initialize customization (timezones, locales, etc) based on the current logged in user.
        $this->customization->setUser($user);
        $this->customization->init();

        $request = $request
            ->withAttribute('user', $user)
            ->withAttribute('is_logged_in', ($user instanceof \Entity\User));

        return $next($request, $response);
    }
}