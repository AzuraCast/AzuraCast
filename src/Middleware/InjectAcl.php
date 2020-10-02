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

    public function __construct(RolePermissionRepository $rolePermRepo)
    {
        $this->rolePermRepo = $rolePermRepo;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $acl = new Acl($this->rolePermRepo);

        $request = $request->withAttribute(ServerRequest::ATTR_ACL, $acl);

        return $handler->handle($request);
    }
}
