<?php
namespace App\Middleware;

use App\Auth;
use App\Customization;
use App\Http\RequestHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = ($this->auth->isLoggedIn()) ? $this->auth->getLoggedInUser() : null;

        // Initialize customization (timezones, locales, etc) based on the current logged in user.
        $this->customization->setUser($user);
        $request = $this->customization->init($request);

        $request = RequestHelper::injectUser($request, $user);

        return $handler->handle($request);
    }
}
