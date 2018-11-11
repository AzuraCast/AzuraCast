<?php
namespace App\Http;

use Azura\Exception;
use App\Entity;
use App\Radio;

class Request extends \Azura\Http\Request
{
    const ATTRIBUTE_STATION = 'station';
    const ATTRIBUTE_STATION_BACKEND = 'station_backend';
    const ATTRIBUTE_STATION_FRONTEND = 'station_frontend';
    const ATTRIBUTE_STATION_REMOTES = 'station_remotes';
    const ATTRIBUTE_USER = 'user';

    /**
     * Get the current user associated with the request, if it's set.
     * Set by @see \App\Middleware\GetCurrentUser
     *
     * @return Entity\User
     * @throws Exception
     */
    public function getUser(): Entity\User
    {
        return $this->_getAttributeOfType(self::ATTRIBUTE_USER, Entity\User::class);
    }

    /**
     * Get the current station associated with the request, if it's set.
     * Set by @see \App\Middleware\GetStation
     *
     * @return Entity\Station
     * @throws Exception
     */
    public function getStation(): Entity\Station
    {
        return $this->_getAttributeOfType(self::ATTRIBUTE_STATION, Entity\Station::class);
    }

    /**
     * Get the current station frontend associated with the request, if it's set.
     * Set by @see \App\Middleware\GetStation
     *
     * @return Radio\Frontend\FrontendAbstract
     * @throws Exception
     */
    public function getStationFrontend(): Radio\Frontend\FrontendAbstract
    {
        return $this->_getAttributeOfType(self::ATTRIBUTE_STATION_FRONTEND, Radio\Frontend\FrontendAbstract::class);
    }

    /**
     * Get the current station backend associated with the request, if it's set.
     * Set by @see \App\Middleware\GetStation
     *
     * @return Radio\Backend\BackendAbstract
     * @throws Exception
     */
    public function getStationBackend(): Radio\Backend\BackendAbstract
    {
        return $this->_getAttributeOfType(self::ATTRIBUTE_STATION_BACKEND, Radio\Backend\BackendAbstract::class);
    }

    /**
     * @return Radio\Remote\AdapterProxy[]
     */
    public function getStationRemotes(): array
    {
        if ($this->hasAttribute(self::ATTRIBUTE_STATION_REMOTES)) {
            return $this->getAttribute(self::ATTRIBUTE_STATION_REMOTES);
        }

        throw new Exception(sprintf('Attribute "%s" was not set.', self::ATTRIBUTE_STATION_REMOTES));
    }
}
