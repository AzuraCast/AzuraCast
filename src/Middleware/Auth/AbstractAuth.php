<?php

declare(strict_types=1);

namespace App\Middleware\Auth;

use App\Acl;
use App\Container\EnvironmentAwareTrait;
use App\Customization;
use App\Entity\AuditLog;
use App\Entity\Repository\UserRepository;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractAuth implements MiddlewareInterface
{
    use EnvironmentAwareTrait;

    public function __construct(
        protected readonly UserRepository $userRepo,
        protected readonly Acl $acl,
        protected readonly Customization $customization
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $customization = $this->customization->withRequest($request);

        // Initialize ACL (can only be initialized after Customization as it contains localizations).
        $acl = $this->acl->withRequest($request);

        $request = $request
            ->withAttribute(ServerRequest::ATTR_LOCALE, $customization->getLocale())
            ->withAttribute(ServerRequest::ATTR_CUSTOMIZATION, $customization)
            ->withAttribute(ServerRequest::ATTR_ACL, $acl);

        // Set the Audit Log user.
        AuditLog::setCurrentUser($request->getAttribute(ServerRequest::ATTR_USER));

        $response = $handler->handle($request);

        AuditLog::setCurrentUser();

        return $response;
    }
}
