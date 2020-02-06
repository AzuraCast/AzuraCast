<?php
namespace App\Http;

use App\Acl;
use App\Entity;
use App\Exception;
use App\Radio;
use App\RateLimit;
use App\Session;
use App\View;
use Mezzio\Session\SessionInterface;

class ServerRequest extends \Slim\Http\ServerRequest
{
    public const ATTR_VIEW = 'app_view';
    public const ATTR_SESSION = 'app_session';
    public const ATTR_SESSION_CSRF = 'app_session_csrf';
    public const ATTR_SESSION_FLASH = 'app_session_flash';
    public const ATTR_ROUTER = 'app_router';
    public const ATTR_RATE_LIMIT = 'app_rate_limit';
    public const ATTR_IS_API_CALL = 'is_api_call';
    public const ATTR_ACL = 'acl';
    public const ATTR_STATION = 'station';
    public const ATTR_STATION_BACKEND = 'station_backend';
    public const ATTR_STATION_FRONTEND = 'station_frontend';
    public const ATTR_STATION_REMOTES = 'station_remotes';
    public const ATTR_USER = 'user';

    /**
     * @return View
     * @throws Exception
     */
    public function getView(): View
    {
        return $this->getAttributeOfClass(self::ATTR_VIEW, View::class);
    }

    /**
     * @return SessionInterface
     * @throws Exception
     */
    public function getSession(): SessionInterface
    {
        return $this->getAttributeOfClass(self::ATTR_SESSION, SessionInterface::class);
    }

    /**
     * @return Session\Csrf
     * @throws Exception
     */
    public function getCsrf(): Session\Csrf
    {
        return $this->getAttributeOfClass(self::ATTR_SESSION_CSRF, Session\Csrf::class);
    }

    /**
     * @return Session\Flash
     * @throws Exception
     */
    public function getFlash(): Session\Flash
    {
        return $this->getAttributeOfClass(self::ATTR_SESSION_FLASH, Session\Flash::class);
    }

    /**
     * @return RouterInterface
     * @throws Exception
     */
    public function getRouter(): RouterInterface
    {
        return $this->getAttributeOfClass(self::ATTR_ROUTER, RouterInterface::class);
    }

    /**
     * @return RateLimit
     * @throws Exception
     */
    public function getRateLimit(): RateLimit
    {
        return $this->getAttributeOfClass(self::ATTR_RATE_LIMIT, RateLimit::class);
    }

    /**
     * Get the remote user's IP address as indicated by HTTP headers.
     * @return string|null
     */
    public function getIp(): ?string
    {
        $params = $this->serverRequest->getServerParams();

        return $params['HTTP_CLIENT_IP']
            ?? $params['HTTP_X_FORWARDED_FOR']
            ?? $params['HTTP_X_FORWARDED']
            ?? $params['HTTP_FORWARDED_FOR']
            ?? $params['HTTP_FORWARDED']
            ?? $params['REMOTE_ADDR']
            ?? null;
    }

    public function isApiCall(): bool
    {
        return $this->serverRequest->getAttribute(self::ATTR_IS_API_CALL, false);
    }

    public function getAcl(): Acl
    {
        return $this->getAttributeOfClass(self::ATTR_ACL, Acl::class);
    }

    /**
     * Get the current user associated with the request, if it's set.
     * Set by @return Entity\User
     * @see \App\Middleware\GetCurrentUser
     *
     */
    public function getUser(): Entity\User
    {
        return $this->getAttributeOfClass(self::ATTR_USER, Entity\User::class);
    }

    /**
     * Get the current station associated with the request, if it's set.
     * Set by @return Entity\Station
     * @throws Exception
     * @see \App\Middleware\GetStation
     *
     */
    public function getStation(): Entity\Station
    {
        return $this->getAttributeOfClass(self::ATTR_STATION, Entity\Station::class);
    }

    /**
     * Get the current station frontend associated with the request, if it's set.
     * Set by @return Radio\Frontend\AbstractFrontend
     * @throws Exception
     * @see \App\Middleware\GetStation
     *
     */
    public function getStationFrontend(): Radio\Frontend\AbstractFrontend
    {
        return $this->getAttributeOfClass(self::ATTR_STATION_FRONTEND, Radio\Frontend\AbstractFrontend::class);
    }

    /**
     * Get the current station backend associated with the request, if it's set.
     * Set by @return Radio\Backend\AbstractBackend
     * @throws Exception
     * @see \App\Middleware\GetStation
     *
     */
    public function getStationBackend(): Radio\Backend\AbstractBackend
    {
        return $this->getAttributeOfClass(self::ATTR_STATION_BACKEND, Radio\Backend\AbstractBackend::class);
    }

    /**
     * @return Radio\Remote\AdapterProxy[]
     * @throws Exception
     */
    public function getStationRemotes(): array
    {
        $remotes = $this->serverRequest->getAttribute(self::ATTR_STATION_REMOTES);

        if (null === $remotes) {
            throw new Exception(sprintf('Attribute "%s" was not set.', self::ATTR_STATION_REMOTES));
        }

        return $remotes;
    }

    /**
     * @param string $attr
     * @param string $class_name
     *
     * @return mixed
     * @throws Exception
     */
    protected function getAttributeOfClass($attr, $class_name)
    {
        $object = $this->serverRequest->getAttribute($attr);
        if ($object instanceof $class_name) {
            return $object;
        }

        throw new Exception(sprintf('Attribute %s must be of type %s.', $attr, $class_name));
    }
}
