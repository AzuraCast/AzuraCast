<?php
namespace AzuraCast\Middleware;

use App\Auth;
use AzuraCast\Customization;
use App\Http\Request;
use App\Http\Response;

/**
 * Get the current user entity object and assign it into the request if it exists.
 */
class GetCurrentUser
{
    /** @var Auth */
    protected $auth;

    /** @var Customization */
    protected $customization;

    public function __construct(Auth $auth, Customization $customization)
    {
        $this->auth = $auth;
        $this->customization = $customization;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     * @throws \App\Exception
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        $user = ($this->auth->isLoggedIn()) ? $this->auth->getLoggedInUser() : null;

        // Initialize customization (timezones, locales, etc) based on the current logged in user.
        $this->customization->setUser($user);
        $request = $this->customization->init($request);

        $request = $request
            ->withAttribute('user', $user)
            ->withAttribute('is_logged_in', ($user instanceof \Entity\User));

        return $next($request, $response);
    }
}
