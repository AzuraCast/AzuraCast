<?php

namespace App\Middleware;

use App\Auth;
use App\Customization;
use App\Entity;
use App\Environment;
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
    protected Entity\Repository\UserRepository $userRepo;

    protected Entity\Settings $settings;

    protected Environment $environment;

    public function __construct(
        Entity\Repository\UserRepository $userRepo,
        Environment $environment,
        Entity\Settings $settings
    ) {
        $this->userRepo = $userRepo;
        $this->environment = $environment;
        $this->settings = $settings;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Initialize the Auth for this request.
        $auth = new Auth(
            $this->userRepo,
            $request->getAttribute(ServerRequest::ATTR_SESSION),
            $this->environment
        );
        $user = ($auth->isLoggedIn()) ? $auth->getLoggedInUser() : null;

        $request = $request
            ->withAttribute(ServerRequest::ATTR_AUTH, $auth)
            ->withAttribute(ServerRequest::ATTR_USER, $user)
            ->withAttribute('is_logged_in', (null !== $user));

        // Initialize Customization (timezones, locales, etc) based on the current logged in user.
        $customization = new Customization($this->settings, $this->environment, $request);

        $request = $request
            ->withAttribute('locale', $customization->getLocale())
            ->withAttribute(ServerRequest::ATTR_CUSTOMIZATION, $customization);

        // Set the Audit Log user.
        Entity\AuditLog::setCurrentUser($user);

        $response = $handler->handle($request);

        Entity\AuditLog::setCurrentUser(null);

        return $response;
    }
}
