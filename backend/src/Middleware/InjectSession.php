<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Cache\CacheNamespace;
use App\Container\SettingsAwareTrait;
use App\Environment;
use App\Http\ServerRequest;
use App\Session\Csrf;
use App\Session\Flash;
use Mezzio\Session\Cache\CacheSessionPersistence;
use Mezzio\Session\LazySession;
use Mezzio\Session\SessionPersistenceInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Inject the session object into the request.
 */
final class InjectSession extends AbstractMiddleware
{
    use SettingsAwareTrait;

    private CacheItemPoolInterface $cachePool;

    public function __construct(
        CacheItemPoolInterface $psrCache,
        private readonly Environment $environment
    ) {
        $this->cachePool = $environment->isCli()
            ? new ArrayAdapter()
            : CacheNamespace::Session->withNamespace($psrCache);
    }

    public function getSessionPersistence(ServerRequest $request): SessionPersistenceInterface
    {
        $alwaysUseSsl = $this->readSettings()->always_use_ssl;
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
            cookieHttpOnly: true,
            autoRegenerate: false
        );
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $sessionPersistence = $this->getSessionPersistence($request);
        $session = new LazySession($sessionPersistence, $request);

        $csrf = new Csrf($request, $session, $this->environment);
        $flash = new Flash($session);

        $request = $request->withAttribute(ServerRequest::ATTR_SESSION, $session)
            ->withAttribute(ServerRequest::ATTR_SESSION_CSRF, $csrf)
            ->withAttribute(ServerRequest::ATTR_SESSION_FLASH, $flash);

        $response = $handler->handle($request);

        return $sessionPersistence->persistSession($session, $response);
    }
}
