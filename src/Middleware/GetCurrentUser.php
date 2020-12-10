<?php

namespace App\Middleware;

use App\Auth;
use App\Customization;
use App\Entity;
use App\Http\ServerRequest;
use DI\FactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Get the current user entity object and assign it into the request if it exists.
 */
class GetCurrentUser implements MiddlewareInterface
{
    protected FactoryInterface $factory;

    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Initialize the Auth for this request.
        $auth = $this->factory->make(
            Auth::class,
            [
                'session' => $request->getAttribute(ServerRequest::ATTR_SESSION),
            ]
        );
        $user = ($auth->isLoggedIn()) ? $auth->getLoggedInUser() : null;

        $request = $request
            ->withAttribute(ServerRequest::ATTR_AUTH, $auth)
            ->withAttribute(ServerRequest::ATTR_USER, $user)
            ->withAttribute('is_logged_in', (null !== $user));

        // Initialize Customization (timezones, locales, etc) based on the current logged in user.
        $customization = $this->factory->make(
            Customization::class,
            [
                'request' => $request,
            ]
        );

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
