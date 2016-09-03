<?php
namespace App;

class Url extends \Phalcon\Mvc\Url
{
    /**
     * @var \App\Config
     */
    protected $_config;

    /**
     * @var \Phalcon\Http\Request
     */
    protected $_request;

    /**
     * @var \Phalcon\Mvc\Dispatcher
     */
    protected $_dispatcher;

    /**
     * @var bool Whether to include the domain in the URLs generated.
     */
    protected $_include_domain = false;


    public function __construct(\App\Config $config, \Phalcon\Http\Request $request, \Phalcon\Dispatcher $dispatcher)
    {
        $this->_config = $config;
        $this->_request = $request;
        $this->_dispatcher = $dispatcher;

        $this->setBaseUri($config->application->base_uri);
        $this->setStaticBaseUri($config->application->static_uri);
    }

    /**
     * Get the URI for the current page.
     *
     * @return mixed
     */
    public function current($absolute = false)
    {
        return $this->getUrl($this->_request->getURI(), $absolute);
    }

    /**
     * Generate a callback-friendly URL.
     */
    public function callback()
    {
        return $this->getUrl($this->routeFromHere(array()), true);
    }

    /**
     * Get the HTTP_REFERER value for the current page.
     *
     * @param null $default_url
     * @return mixed
     */
    public function referrer($default_url = null)
    {
        return $this->getUrl($this->_request->getHTTPReferer());
    }

    /**
     * Return the base URL of the site.
     *
     * @return mixed
     */
    public function baseUrl($include_host = false)
    {
        $uri = $this->get('');

        if ($include_host)
            return $this->addSchemePrefix($uri);
        else
            return $this->getUrl($uri);
    }

    /**
     * Return the static URL for a given path segment.
     *
     * @param null $file_name
     * @return string The routed URL.
     */
    public function content($file_name = NULL)
    {
        return $this->getStatic($file_name);
    }

    /**
     * Generate a route using the ZendFramework 1 MVC route standard.
     *
     * @param $path_info
     * @return string The routed URL.
     */
    public function route($path_info = array(), $absolute = null)
    {
        $router_config = $this->_config->routes->toArray();

        $url_separator = '/';
        $default_module = $router_config['default_module'];

        $components = array(
            'module'    => $default_module,
            'controller' => $router_config['default_controller'],
            'action'    => $router_config['default_action'],
        );

        if (isset($path_info['module']))
        {
            $components['module'] = $path_info['module'];
            unset($path_info['module']);
        }
        if (isset($path_info['controller']))
        {
            $components['controller'] = $path_info['controller'];
            unset($path_info['controller']);
        }
        if (isset($path_info['action']))
        {
            $components['action'] = $path_info['action'];
            unset($path_info['action']);
        }
        if (isset($path_info['params']))
        {
            $path_info = array_merge($path_info, $path_info['params']);
            unset($path_info['params']);
        }

        // Handle the legacy "default" module being so-named.
        if ($components['module'] == 'default')
            $components['module'] = $default_module;

        // Special exception for homepage.
        if ($components['module'] == $default_module &&
            $components['controller'] == $router_config['default_controller'] &&
            $components['action'] == $router_config['default_action'] &&
            empty($path_info)) {
            return $this->get('');
        }

        // Otherwise compile URL using a uniform format.
        $url_parts = array();

        if ($components['module'] != $default_module)
            $url_parts[] = $components['module'];

        $url_parts[] = $components['controller'];
        $url_parts[] = $components['action'];

        $path_info = array_filter($path_info);

        if (count($path_info) > 0)
        {
            foreach ((array)$path_info as $param_key => $param_value)
            {
                $url_parts[] = urlencode($param_key);
                $url_parts[] = urlencode($param_value);
            }
        }

        $url_full = implode($url_separator, $url_parts);
        return $this->getUrl($this->get($url_full), $absolute);
    }

    /**
     * Generate a route based on the current URL.
     *
     * @param $path_info
     * @return string The routed URL.
     */
    public function routeFromHere($path_info)
    {
        $new_path = array(
            'module'        => $this->_dispatcher->getModuleName(),
            'controller'    => $this->_dispatcher->getControllerName(),
            'action'        => $this->_dispatcher->getActionName(),
            'params'        => (array)$this->_dispatcher->getParams(),
        );

        if (isset($path_info['module']))
        {
            $new_path['module'] = $path_info['module'];
            unset($path_info['module']);
        }
        if (isset($path_info['controller']))
        {
            $new_path['controller'] = $path_info['controller'];
            unset($path_info['controller']);
        }
        if (isset($path_info['action']))
        {
            $new_path['action'] = $path_info['action'];
            unset($path_info['action']);
        }

        if (count($path_info) > 0)
        {
            foreach ((array)$path_info as $param_key => $param_value)
            {
                $new_path['params'][$param_key] = $param_value;
            }
        }

        if (isset($new_path['params']['name']))
        {
            // Allow support for named routes.
            $route_name = $new_path['params']['name'];
            unset($new_path['params']['name']);

            return $this->named($route_name, $new_path['params']);
        }
        else
        {
            return $this->route($new_path);
        }
    }

    public function getSchemePrefixSetting()
    {
        return $this->_include_domain;
    }

    public function forceSchemePrefix($new_value = true)
    {
        $this->_include_domain = $new_value;
    }

    public function addSchemePrefix($url_raw)
    {
        return $this->getUrl($url_raw, true);
    }

    public function getUrl($url_raw, $absolute = false)
    {
        // Ignore preformed URLs.
        if (stristr($url_raw, '://'))
            return $url_raw;

        // Retrieve domain from either MVC controller or config file.
        if ($this->_include_domain || $absolute) {

            $url_domain = \Entity\Settings::getSetting('base_url', '');

            if (empty($url_domain))
                $url_domain = $this->_config->application->base_url;
            else
                $url_domain = ((APP_IS_SECURE) ? 'https://' : 'http://') . $url_domain;

            if (empty($url_domain))
            {
                $http_host = trim($this->_request->getHttpHost(), ':');

                if (!empty($http_host))
                    $url_domain = ((APP_IS_SECURE) ? 'https://' : 'http://') . $http_host;
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
    public function named($route_name, $route_params = array())
    {
        $route_params = (array)$route_params;
        $route_params['for'] = $route_name;

        return $this->get($route_params);
    }
    
    /**
     * Return URL for user-uploaded content.
     *
     * @param null $path
     * @return string
     */
    public function upload($path = NULL)
    {
        return $this->content($path);
    }
}