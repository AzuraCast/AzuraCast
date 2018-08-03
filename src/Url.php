<?php
namespace App;

use Slim\Router;

class Url
{
    /** @var Router */
    protected $router;

    /** @var string */
    protected $base_url;

    /** @var bool Whether to include the domain in the URLs generated. */
    protected $include_domain = false;

    public function __construct(Router $router, $base_url)
    {
        $this->router = $router;
        $this->base_url = $base_url;
    }

    /**
     * Returns the raw Base URL component.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->base_url;
    }

    /**
     * Get the URI for the current page.
     *
     * @return mixed
     */
    public function current($absolute = false)
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
     * @return mixed
     */
    public function referrer($default_url = null)
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            return $this->getUrl($_SERVER['HTTP_REFERER']);
        }

        return $default_url;
    }

    /**
     * Return the base URL of the site.
     *
     * @return mixed
     */
    public function baseUrl($include_host = false)
    {
        $uri = $this->router->pathFor('home');

        if ($include_host) {
            return $this->addSchemePrefix($uri);
        } else {
            return $this->getUrl($uri);
        }
    }

    /**
     * Return the static URL for a given path segment.
     *
     * @param null $file_name
     * @return string The routed URL.
     */
    public function content($file_name = null)
    {
        return '/static/' . $file_name;
    }

    public function getSchemePrefixSetting()
    {
        return $this->include_domain;
    }

    public function forceSchemePrefix($new_value = true)
    {
        $this->include_domain = $new_value;
    }

    public function addSchemePrefix($url_raw)
    {
        return $this->getUrl($url_raw, true);
    }

    public function getUrl($url_raw, $absolute = false)
    {
        // Ignore preformed URLs.
        if (false !== strpos($url_raw, '://')) {
            return $url_raw;
        }

        // Retrieve domain from either MVC controller or config file.
        if ($this->include_domain || $absolute) {
            $url_raw = $this->base_url . $url_raw;
        }

        return $url_raw;
    }

    /**
     * Simpler format for calling "named" routes with parameters.
     *
     * @param $route_name
     * @param array $route_params
     * @param boolean $absolute Whether to include the full URL.
     * @return string
     */
    public function named($route_name, $route_params = [], $absolute = false)
    {
        return $this->getUrl($this->router->pathFor($route_name, $route_params), $absolute);
    }

    /**
     * Return URL for user-uploaded content.
     *
     * @param null $path
     * @return string
     */
    public function upload($path = null)
    {
        return $this->content($path);
    }
}