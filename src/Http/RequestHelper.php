<?php
namespace App\Http;

use App\Acl;
use App\Entity;
use App\Radio;
use Azura\Exception;
use Psr\Http\Message\ServerRequestInterface;

class RequestHelper extends \Azura\Http\RequestHelper
{
    public const ATTR_IS_API_CALL = 'is_api_call';
    public const ATTR_ACL = 'acl';
    public const ATTR_STATION = 'station';
    public const ATTR_STATION_BACKEND = 'station_backend';
    public const ATTR_STATION_FRONTEND = 'station_frontend';
    public const ATTR_STATION_REMOTES = 'station_remotes';
    public const ATTR_USER = 'user';

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    public static function isApiCall(ServerRequestInterface $request): bool
    {
        return $request->getAttribute(self::ATTR_IS_API_CALL, false);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Acl $acl
     * @return ServerRequestInterface
     */
    public static function injectAcl(ServerRequestInterface $request, Acl $acl): ServerRequestInterface
    {
        return $request->withAttribute(self::ATTR_ACL, $acl);
    }

    /**
     * @param ServerRequestInterface $request
     * @return Acl
     * @throws Exception
     */
    public static function getAcl(ServerRequestInterface $request): Acl
    {
        return self::getAttributeOfClass($request, self::ATTR_ACL, Acl::class);
    }

    /**
     * Inject the current user associated with the request.
     *
     * @param ServerRequestInterface $request
     * @param Entity\User|null $user
     *
     * @return ServerRequestInterface
     */
    public static function injectUser(ServerRequestInterface $request, ?Entity\User $user): ServerRequestInterface
    {
        return $request->withAttribute(self::ATTR_USER, $user)
            ->withAttribute('is_logged_in', ($user instanceof Entity\User));
    }

    /**
     * Get the current user associated with the request, if it's set.
     * Set by @see \App\Middleware\GetCurrentUser
     *
     * @param ServerRequestInterface $request
     * @return Entity\User
     */
    public static function getUser(ServerRequestInterface $request): Entity\User
    {
        return self::getAttributeOfClass($request, self::ATTR_USER, Entity\User::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Entity\Station $station
     * @param Radio\Backend\AbstractBackend $backend
     * @param Radio\Frontend\AbstractFrontend $frontend
     * @param Radio\Remote\AdapterProxy[] $remotes
     *
     * @return ServerRequestInterface
     */
    public static function injectStationComponents(
        ServerRequestInterface $request,
        Entity\Station $station,
        Radio\Backend\AbstractBackend $backend,
        Radio\Frontend\AbstractFrontend $frontend,
        array $remotes
    ): ServerRequestInterface {
        return $request->withAttribute(self::ATTR_STATION, $station)
            ->withAttribute(self::ATTR_STATION_BACKEND, $backend)
            ->withAttribute(self::ATTR_STATION_FRONTEND, $frontend)
            ->withAttribute(self::ATTR_STATION_REMOTES, $remotes);
    }

    /**
     * Get the current station associated with the request, if it's set.
     * Set by @see \App\Middleware\GetStation
     *
     * @param ServerRequestInterface $request
     *
     * @return Entity\Station
     * @throws Exception
     */
    public static function getStation(ServerRequestInterface $request): Entity\Station
    {
        return self::getAttributeOfClass($request, self::ATTR_STATION, Entity\Station::class);
    }

    /**
     * Get the current station frontend associated with the request, if it's set.
     * Set by @see \App\Middleware\GetStation
     *
     * @param ServerRequestInterface $request
     *
     * @return Radio\Frontend\AbstractFrontend
     * @throws Exception
     */
    public static function getStationFrontend(ServerRequestInterface $request): Radio\Frontend\AbstractFrontend
    {
        return self::getAttributeOfClass($request, self::ATTR_STATION_FRONTEND, Radio\Frontend\AbstractFrontend::class);
    }

    /**
     * Get the current station backend associated with the request, if it's set.
     * Set by @see \App\Middleware\GetStation
     *
     * @param ServerRequestInterface $request
     *
     * @return Radio\Backend\AbstractBackend
     * @throws Exception
     */
    public static function getStationBackend(ServerRequestInterface $request): Radio\Backend\AbstractBackend
    {
        return self::getAttributeOfClass($request, self::ATTR_STATION_BACKEND, Radio\Backend\AbstractBackend::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @return Radio\Remote\AdapterProxy[]
     * @throws Exception
     */
    public static function getStationRemotes(ServerRequestInterface $request): array
    {
        $remotes = $request->getAttribute(self::ATTR_STATION_REMOTES);

        if (null === $remotes) {
            throw new Exception(sprintf('Attribute "%s" was not set.', self::ATTR_STATION_REMOTES));
        }

        return $remotes;
    }
}
