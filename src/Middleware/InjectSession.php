<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Entity;
use App\Environment;
use App\Http\ServerRequest;
use App\Session\Csrf;
use App\Session\Flash;
use Mezzio\Session\Cache\CacheSessionPersistence;
use Mezzio\Session\LazySession;
use Mezzio\Session\SessionPersistenceInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ProxyAdapter;

/**
 * Inject the session object into the request.
 */
final class InjectSession implements MiddlewareInterface
{
    private CacheItemPoolInterface $cachePool;

    public function __construct(
        CacheItemPoolInterface $cachePool,
        private readonly Entity\Repository\SettingsRepository $settingsRepo,
        private readonly Environment $environment
    ) {
        if ($environment->isCli()) {
            $cachePool = new ArrayAdapter();
        }

        $this->cachePool = new ProxyAdapter($cachePool, 'session.');
    }

    public function getSessionPersistence(ServerRequestInterface $request): SessionPersistenceInterface
    {
        $alwaysUseSsl = $this->settingsRepo->readSettings()->getAlwaysUseSsl();
        $isHttpsUrl = ('https' === $request->getUri()->getScheme());

        return new CacheSessionPersistence(
            cache: $this->cachePool,
            cookieName: 'app_session',
            cookiePath: '/',
            cacheLimiter: 'nocache',
            cacheExpire: 43200,
            lastModified: time(),
            persistent: true,
            cookieSecure: $alwaysUseSsl && $isHttpsUrl,
            cookieHttpOnly: true
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $sessionPersistence = $this->getSessionPersistence($request);
        $session = new LazySession($sessionPersistence, $request);

        $csrf = new Csrf($session, $this->environment);
        $flash = new Flash($session);

        $request = $request->withAttribute(ServerRequest::ATTR_SESSION, $session)
            ->withAttribute(ServerRequest::ATTR_SESSION_CSRF, $csrf)
            ->withAttribute(ServerRequest::ATTR_SESSION_FLASH, $flash);

        $response = $handler->handle($request);
        return $sessionPersistence->persistSession($session, $response);
    }
}
