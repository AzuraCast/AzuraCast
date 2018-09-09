<?php
namespace App\Http;

use App\Entity;
use App\Exception;
use App\View;
use App\Radio;
use App\Session;
use Psr\Http\Message\UriInterface;
use Slim\Route;

class Request extends \Slim\Http\Request
{
    const ATTRIBUTE_ROUTER = 'router';
    const ATTRIBUTE_SESSION = 'session';
    const ATTRIBUTE_STATION = 'station';
    const ATTRIBUTE_STATION_BACKEND = 'station_backend';
    const ATTRIBUTE_STATION_FRONTEND = 'station_frontend';
    const ATTRIBUTE_STATION_REMOTES = 'station_remotes';
    const ATTRIBUTE_USER = 'user';
    const ATTRIBUTE_VIEW = 'view';

    /**
     * Get the current URI with redundant "http://url:80/" and "https://url:443/" filtered out.
     *
     * @return UriInterface
     */
    public function getFilteredUri(): UriInterface
    {
        if (($this->uri->getScheme() === 'http' && $this->uri->getPort() === 80)
            || ($this->uri->getScheme() === 'https' && $this->uri->getPort() === 443)) {
            return $this->uri->withPort(null);
        }

        return $this->uri;
    }

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
     * Pull the current route, if it's generated yet.
     *
     * @return Route
     * @throws Exception
     */
    public function getCurrentRoute(): Route
    {
        if ($this->hasAttribute('route')) {
            return $this->getAttribute('route');
        }

        throw new Exception("Route does not exist.");
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
     * Get the application's Router.
     * Set by @see \App\Middleware\EnableRouter
     *
     * @return Router
     * @throws Exception
     */
    public function getRouter(): Router
    {
        return $this->_getAttributeOfType(self::ATTRIBUTE_ROUTER, Router::class);
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
     * @return Radio\Remote\RemoteAbstract[]
     */
    public function getStationRemotes(): array
    {
        if ($this->hasAttribute(self::ATTRIBUTE_STATION_REMOTES)) {
            return $this->getAttribute(self::ATTRIBUTE_STATION_REMOTES);
        }

        throw new Exception(sprintf('Attribute "%s" was not set.', self::ATTRIBUTE_STATION_REMOTES));
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
