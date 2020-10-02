<?php
namespace App\Middleware;

use App\Http\ServerRequest;
use App\Session\Csrf;
use App\Session\Flash;
use App\Settings;
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
    protected SessionPersistenceInterface $sessionPersistence;

    protected Settings $settings;

    public function __construct(
        SessionPersistenceInterface $sessionPersistence,
        Settings $settings
    ) {
        $this->sessionPersistence = $sessionPersistence;
        $this->settings = $settings;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = new LazySession($this->sessionPersistence, $request);

        $csrf = new Csrf($session, $this->settings);
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
