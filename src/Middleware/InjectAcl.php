<?php
namespace App\Middleware;

use App\Acl;
use App\Http\RequestHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Inject core services into the request object for use further down the stack.
 */
class InjectAcl implements MiddlewareInterface
{
    /** @var Acl */
    protected $acl;

    public function __construct(Acl $acl) {
        $this->acl = $acl;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = RequestHelper::injectAcl($request, $this->acl);

        return $handler->handle($request);
    }
}
