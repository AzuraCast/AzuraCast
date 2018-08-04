<?php
namespace App\Http;

use App\Entity;
use App\Exception;
use App\Mvc\View;
use App\Radio;
use App\Session;

class Request extends \Slim\Http\Request
{
    const ATTRIBUTE_VIEW = 'view';
    const ATTRIBUTE_USER = 'user';
    const ATTRIBUTE_SESSION = 'session';
    const ATTRIBUTE_STATION = 'station';
    const ATTRIBUTE_STATION_BACKEND = 'station_backend';
    const ATTRIBUTE_STATION_FRONTEND = 'station_frontend';

    /**
     * Detect if a parameter exists in the request.
     *
     * @param  string $key The parameter key.
     * @return bool Whether the key exists.
     */
    public function hasParam($key): bool
    {
        return ($this->getParam($key, null) !== null);
    }

    /**
     * Detect if an attribute exists in the request.
     *
     * @param $key
     * @return bool
     */
    public function hasAttribute($key): bool
    {
        return $this->attributes->has($key);
    }

    /**
     * Get the View associated with the request, if it's set.
     * Set by @see \App\Middleware\EnableView
     *
     * @return View
     * @throws Exception
     */
    public function getView(): View
    {
        $view = $this->getAttribute(self::ATTRIBUTE_VIEW);
        if ($view instanceof View) {
            return $view;
        }

        throw new Exception('No view present in this request.');
    }

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
     * Get the current session manager associated with the request.
     *
     * @return Session
     * @throws Exception
     */
    public function getSession(): Session
    {
        return $this->_getAttributeOfType(self::ATTRIBUTE_SESSION, Session::class);
    }

    /**
     * Internal handler for retrieving attributes from the request and verifying their type.
     *
     * @param $attribute_name
     * @param $attribute_class
     * @return mixed
     * @throws Exception
     */
    protected function _getAttributeOfType($attribute_name, $attribute_class)
    {
        if ($this->hasAttribute($attribute_name)) {
            $attr = $this->getAttribute($attribute_name);
            if ($attr instanceof $attribute_class) {
                return $attr;
            }

            throw new Exception(sprintf('Attribute "%s" is not of type "%s".', $attribute_name, $attribute_class));
        }

        throw new Exception(sprintf('Attribute "%s" was not set.', $attribute_name));
    }
}
