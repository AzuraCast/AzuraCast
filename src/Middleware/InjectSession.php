<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Environment;
use App\Http\ServerRequest;
use App\Session\Csrf;
use App\Session\Flash;
use Mezzio\Session\LazySession;
use Mezzio\Session\SessionPersistenceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Inject the session object into the request.
 */
class InjectSession implements MiddlewareInterface
{
    public function __construct(
        protected SessionPersistenceInterface $sessionPersistence,
        protected Environment $environment
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = new LazySession($this->sessionPersistence, $request);

        $csrf = new Csrf($session, $this->environment);
        Csrf::setInstance($csrf);

        $flash = new Flash($session);
        Flash::setInstance($flash);

        $request = $request->withAttribute(ServerRequest::ATTR_SESSION, $session)
            ->withAttribute(ServerRequest::ATTR_SESSION_CSRF, $csrf)
            ->withAttribute(ServerRequest::ATTR_SESSION_FLASH, $flash);

        $response = $handler->handle($request);
        return $this->sessionPersistence->persistSession($session, $response);
    }
}
