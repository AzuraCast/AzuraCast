<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Acl;
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
    public function __construct(
        protected Entity\Repository\UserRepository $userRepo,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Environment $environment,
        protected Acl $acl
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Initialize the Auth for this request.
        $auth = new Auth(
            userRepo: $this->userRepo,
            session: $request->getAttribute(ServerRequest::ATTR_SESSION),
            environment: $this->environment,
        );
        $user = ($auth->isLoggedIn()) ? $auth->getLoggedInUser() : null;

        $request = $request
            ->withAttribute(ServerRequest::ATTR_AUTH, $auth)
            ->withAttribute(ServerRequest::ATTR_USER, $user)
            ->withAttribute('is_logged_in', (null !== $user));

        // Initialize Customization (timezones, locales, etc) based on the current logged in user.
        $customization = new Customization(
            environment: $this->environment,
            settingsRepo: $this->settingsRepo,
            request: $request
        );

        // Initialize ACL (can only be initialized after Customization as it contains localizations).
        $acl = $this->acl->withRequest($request);

        $request = $request
            ->withAttribute(ServerRequest::ATTR_LOCALE, $customization->getLocale())
            ->withAttribute(ServerRequest::ATTR_CUSTOMIZATION, $customization)
            ->withAttribute(ServerRequest::ATTR_ACL, $acl);

        // Set the Audit Log user.
        Entity\AuditLog::setCurrentUser($user);

        $response = $handler->handle($request);

        Entity\AuditLog::setCurrentUser();

        return $response;
    }
}
