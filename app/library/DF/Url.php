<?php
namespace DF;

class Url
{
    /**
     * Get the URI for the current page.
     *
     * @param \Phalcon\DiInterface $di
     * @return mixed
     */
    public static function current(\Phalcon\DiInterface $di = null)
    {
        $di = self::getDi($di);
        return $di->get('request')->getURI();
    }

    /**
     * Get the HTTP_REFERER value for the current page.
     *
     * @param null $default_url
     * @param \Phalcon\DiInterface $di
     * @return mixed
     */
    public static function referrer($default_url = null, \Phalcon\DiInterface $di = null)
    {
        $di = self::getDi($di);
        return $di->get('request')->getHTTPReferer();
    }

    /**
     * Return the base URL of the site.
     *
     * @return mixed
     */
    public static function baseUrl($include_host = false, \Phalcon\DiInterface $di = null)
    {
        $di = self::getDi($di);
        $uri = $di->get('url')->get('');

        if ($include_host)
            return ((DF_IS_SECURE)? 'https://' : 'http://') . $di->get('request')->getHttpHost() . $uri;
        else
            return $uri;
    }

    /**
     * Return the static URL for a given path segment.
     *
     * @param null $file_name
     * @return string The routed URL.
     */
    public static function content($file_name = NULL)
    {
        $di = \Phalcon\Di::getDefault();
        return $di->get('url')->getStatic($file_name);
    }

    /**
     * Generate a route using the ZendFramework 1 MVC route standard.
     *
     * @param $path_info
     * @param \Phalcon\DiInterface $di
     * @return string The routed URL.
     */
    public static function route($path_info = array(), \Phalcon\DiInterface $di = null)
    {
        $di = self::getDi($di);
        $router = $di->get('router');

        $url_separator = '/';
        $default_module = $router->getDefaultModule();

        $components = array(
            'module'    => $default_module,
            'controller' => $router->getDefaultController(),
            'action'    => $router->getDefaultAction(),
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
            $components['controller'] == $router->getDefaultController() &&
            $components['action'] == $router->getDefaultAction() &&
            empty($path_info)) {
            return $di->get('url')->get('');
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
        return $di->get('url')->get($url_full);
    }

    /**
     * Generate a route based on the current URL.
     *
     * @param $path_info
     * @param \Phalcon\DiInterface $di
     * @return string The routed URL.
     */
    public static function routeFromHere($path_info, \Phalcon\DiInterface $di = null)
    {
        $di = self::getDi($di);

        $dispatcher = $di->get('dispatcher');
        $new_path = array(
            'module'        => $dispatcher->getModuleName(),
            'controller'    => $dispatcher->getControllerName(),
            'action'        => $dispatcher->getActionName(),
            'params'        => (array)$dispatcher->getParams(),
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

        return self::route($new_path);
    }

    public static function getDi(\Phalcon\DiInterface $di = null)
    {
        if ($di instanceof \Phalcon\DiInterface)
            return $di;
        else
            return  \Phalcon\Di::getDefault();
    }
}