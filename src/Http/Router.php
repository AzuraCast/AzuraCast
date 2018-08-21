<?php
namespace App\Http;

use App\Entity\Repository\SettingsRepository;
use Slim\Route;

class Router extends \Slim\Router
{
    /** @var bool Whether to include the domain in the URLs generated. */
    protected $include_domain = false;

    /** @var Route */
    protected $current_route;

    /** @var array */
    protected $current_query_params = [];

    public function setCurrentRoute(Route $route, array $query_params = [])
    {
        $this->current_route = $route;
        $this->current_query_params = $query_params;
    }

    public function getCurrentRoute(): ?Route
    {
        return $this->current_route;
    }

    /**
     * Dynamically calculate the base URL the first time it's called, if it is at all in the request.
     *
     * @return string|null
     */
    public function getBaseUrl(): ?string
    {
        static $base_url;

        if (!$base_url)
        {
            /** @var SettingsRepository $settings_repo */
            $settings_repo = $this->container[SettingsRepository::class];

            $base_url = $settings_repo->getSetting('base_url', '');
            $prefer_browser_url = (bool)$settings_repo->getSetting('prefer_browser_url', 0);

            $http_host = $_SERVER['HTTP_HOST'] ?? '';
            $ignore_hosts = ['localhost', 'nginx'];

            if (!empty($http_host) && !in_array($http_host, $ignore_hosts) && ($prefer_browser_url || empty($base_url))) {
                $base_url = $http_host;
            }

            if (!empty($base_url)) {
                $always_use_ssl = (bool)$settings_repo->getSetting('always_use_ssl', 0);
                $base_url_schema = (APP_IS_SECURE || $always_use_ssl) ? 'https://' : 'http://';

                $base_url = $base_url_schema.$base_url;
            }
        }

        return $base_url;
    }

    /**
     * Get the URI for the current page.
     *
     * @param bool $absolute
     * @return string
     */
    public function current($absolute = false): string
    {
        if (!empty($_SERVER['REQUEST_URI'])) {
            return $this->getUrl($_SERVER['REQUEST_URI'], $absolute);
        } else {
            return '';
        }
    }

    /**
     * Get the HTTP_REFERER value for the current page.
     *
     * @param null $default_url
     * @return string|null
     */
    public function referrer($default_url = null): ?string
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            return $this->getUrl($_SERVER['HTTP_REFERER']);
        }

        return $default_url;
    }

    public function getSchemePrefixSetting(): bool
    {
        return $this->include_domain;
    }

    public function forceSchemePrefix($new_value = true)
    {
        $this->include_domain = (bool)$new_value;
    }

    public function addSchemePrefix($url_raw)
    {
        return $this->getUrl($url_raw, true);
    }

    /**
     * Compose a URL, returning an absolute URL (including base URL) if the current settings or this function's parameters
     * indicate an absolute URL is necessary
     *
     * @param $url_raw
     * @param bool $absolute
     * @return string
     */
    public function getUrl($url_raw, $absolute = false)
    {
        // Ignore preformed URLs.
        if (false !== strpos($url_raw, '://')) {
            return $url_raw;
        }

        // Retrieve domain from either MVC controller or config file.
        if ($this->include_domain || $absolute) {
            $url_raw = $this->getBaseUrl() . $url_raw;
        }

        return $url_raw;
    }

    /**
     * Simpler format for calling "named" routes with parameters.
     *
     * @param $route_name
     * @param array $route_params
     * @param array $query_params
     * @param boolean $absolute Whether to include the full URL.
     * @return string
     */
    public function named($route_name, $route_params = [], array $query_params = [], $absolute = false): string
    {
        return $this->getUrl($this->pathFor($route_name, $route_params, $query_params), $absolute);
    }

    /**
     * Return a named route based on the current page and its route arguments.
     *
     * @param null $route_name
     * @param array $route_params
     * @param array $query_params
     * @param bool $absolute
     * @return string
     */
    public function fromHere($route_name = null, array $route_params = [], array $query_params = [], $absolute = false): string
    {
        if ($route_name === null) {
            if ($this->current_route) {
                $route_name = $this->current_route->getName();
            } else {
                throw new \InvalidArgumentException('Cannot specify a null route name if no existing route is configured.');
            }
        }

        if ($this->current_route) {
            $route_params = array_merge($this->current_route->getArguments(), $route_params);
        }

        if ($this->current_query_params) {
            $query_params = array_merge($this->current_query_params, $query_params);
        }

        return $this->named($route_name, $route_params, $query_params, $absolute);
    }
}
