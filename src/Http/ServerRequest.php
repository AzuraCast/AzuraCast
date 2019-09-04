<?php
namespace App\Http;

use App\Acl;
use App\Entity;
use App\Radio;
use Azura\Exception;

class ServerRequest extends \Azura\Http\ServerRequest
{
    public const ATTR_IS_API_CALL = 'is_api_call';
    public const ATTR_ACL = 'acl';
    public const ATTR_STATION = 'station';
    public const ATTR_STATION_BACKEND = 'station_backend';
    public const ATTR_STATION_FRONTEND = 'station_frontend';
    public const ATTR_STATION_REMOTES = 'station_remotes';
    public const ATTR_USER = 'user';

    /**
     * @return bool
     */
    public function isApiCall(): bool
    {
        return $this->serverRequest->getAttribute(self::ATTR_IS_API_CALL, false);
    }

    /**
     * @return Acl
     * @throws Exception
     */
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
}
