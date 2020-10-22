<?php

namespace App\Middleware;

use App\Acl;
use App\Entity\Repository\RolePermissionRepository;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Inject core services into the request object for use further down the stack.
 */
class InjectAcl implements MiddlewareInterface
{
    protected RolePermissionRepository $rolePermRepo;
    protected Acl $acl;

    public function __construct(RolePermissionRepository $rolePermRepo, Acl $acl)
    {
        $this->rolePermRepo = $rolePermRepo;
        $this->acl = $acl;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute(ServerRequest::ATTR_ACL, $this->acl);

        return $handler->handle($request);
    }
}
