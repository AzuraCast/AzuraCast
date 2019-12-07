<?php
namespace App\Middleware;

use App\Auth;
use App\Customization;
use App\Entity\AuditLog;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Get the current user entity object and assign it into the request if it exists.
 */
class GetCurrentUser implements MiddlewareInterface
{
    protected Auth $auth;

    protected Customization $customization;

    public function __construct(Auth $auth, Customization $customization)
    {
        $this->auth = $auth;
        $this->customization = $customization;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->auth->setSession($request->getAttribute(ServerRequest::ATTR_SESSION));

        $user = ($this->auth->isLoggedIn()) ? $this->auth->getLoggedInUser() : null;

        // Initialize customization (timezones, locales, etc) based on the current logged in user.
        $this->customization->setUser($user);
        $request = $this->customization->init($request);

        // Set the Audit Log user.
        AuditLog::setCurrentUser($user);

        $request = $request->withAttribute(ServerRequest::ATTR_USER, $user)
            ->withAttribute('is_logged_in', (null !== $user));

        return $handler->handle($request);
    }
}
