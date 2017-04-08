<?php
namespace App;

use Interop\Container\ContainerInterface;

class Url
{
    /** @var ContainerInterface */
    protected $di;

    /** @var \App\Config */
    protected $config;

    /** @var bool Whether to include the domain in the URLs generated. */
    protected $include_domain = false;

    public function __construct(ContainerInterface $di)
    {
        $this->di = $di;
        $this->config = $di['config'];

        /*
        $this->setBaseUri($this->config->application->base_uri);
        */
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
     * Generate a callback-friendly URL.
     */
    public function callback()
    {
        return $this->getUrl($this->routeFromHere([]), true);
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

        return null;
    }

    /**
     * Return the base URL of the site.
     *
     * @return mixed
     */
    public function baseUrl($include_host = false)
    {
        $router = $this->di['router'];
        $uri = $router->pathFor('home');

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
        return $this->config->application->static_uri . $file_name;
    }

    /**
     * Generate a route using the ZendFramework 1 MVC route standard.
     *
     * @param $path_info
     * @return string The routed URL.
     */
    public function route($path_info = [], $absolute = null)
    {
        $router = $this->di['router'];

        $default_module = 'frontend';
        $components = [
            'module' => $default_module,
            'controller' => 'index',
            'action' => 'index',
        ];

        if (isset($path_info['module'])) {
            $components['module'] = $path_info['module'];
            unset($path_info['module']);
        }
        if (isset($path_info['controller'])) {
            $components['controller'] = $path_info['controller'];
            unset($path_info['controller']);
        }
        if (isset($path_info['action'])) {
            $components['action'] = $path_info['action'];
            unset($path_info['action']);
        }
        if (isset($path_info['params'])) {
            $path_info = array_merge($path_info, $path_info['params']);
            unset($path_info['params']);
        }

        // Handle the legacy "default" module being so-named.
        if ($components['module'] == 'default') {
            $components['module'] = $default_module;
        }

        // Special exception for homepage.
        if ($components['module'] == $default_module &&
            $components['controller'] == 'index' &&
            $components['action'] == 'index' &&
            empty($path_info)
        ) {
            return $router->pathFor('home');
        }

        // Otherwise compile URL using a uniform format.
        $url_parts = [];

        if ($components['module'] != $default_module) {
            $url_parts[] = $components['module'];
        }

        $url_parts[] = $components['controller'];
        $url_parts[] = $components['action'];

        $router_path = implode(':', $url_parts);

        return $this->getUrl($router->pathFor($router_path, $path_info), $absolute);
    }

    protected $current_route;

    public function setCurrentRoute($route_info)
    {
        $this->current_route = $route_info;
    }

    /**
     * Generate a route based on the current URL.
     *
     * @param $path_info
     * @return string The routed URL.
     */
    public function routeFromHere($path_info)
    {
        $new_path = (array)$this->current_route;

        if (isset($path_info['module'])) {
            $new_path['module'] = $path_info['module'];
            unset($path_info['module']);
        }
        if (isset($path_info['controller'])) {
            $new_path['controller'] = $path_info['controller'];
            unset($path_info['controller']);
        }
        if (isset($path_info['action'])) {
            $new_path['action'] = $path_info['action'];
            unset($path_info['action']);
        }

        if (count($path_info) > 0) {
            foreach ((array)$path_info as $param_key => $param_value) {
                $new_path['params'][$param_key] = $param_value;
            }
        }

        if (isset($new_path['params']['name'])) {
            // Allow support for named routes.
            $route_name = $new_path['params']['name'];
            unset($new_path['params']['name']);

            return $this->named($route_name, $new_path['params']);
        } else {
            return $this->route($new_path);
        }
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
        if (stristr($url_raw, '://')) {
            return $url_raw;
        }

        // Retrieve domain from either MVC controller or config file.
        if ($this->include_domain || $absolute) {

            $url_domain = $this->di['em']->getRepository('Entity\Settings')->getSetting('base_url', '');

            if (empty($url_domain)) {
                $url_domain = $this->config->application->base_url;
            } else {
                $url_domain = ((APP_IS_SECURE) ? 'https://' : 'http://') . $url_domain;
            }

            if (empty($url_domain)) {
                $http_host = trim($_SERVER['HTTP_HOST'], ':');

                if (!empty($http_host)) {
                    $url_domain = ((APP_IS_SECURE) ? 'https://' : 'http://') . $http_host;
                }
            }

            $url_raw = $url_domain . $url_raw;
        }

        return $url_raw;
    }

    /**
     * Simpler format for calling "named" routes with parameters.
     *
     * @param $route_name
     * @param array $route_params
     * @return string
     */
    public function named($route_name, $route_params = [])
    {
        $router = $this->di['router'];

        return $router->pathFor($route_name, $route_params);
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