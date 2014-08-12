<?php
namespace DF;

class Url
{
    static $base_url;

    /**
     * Returns the baseUrl
     *
     * @throws \Zend_Exception
     * @return string
     */
    public static function baseUrl()
    {
        if (self::$base_url !== NULL)
        {
            return self::$base_url;
        }
        else
        {
            $config = \Zend_Registry::get('config');

            if ($config->application->base_url)
            {
                $base_url = $config->application->base_url;

                if (DF_IS_SECURE)
                    $base_url = str_replace('http://', 'https://', $base_url);

                return $base_url;
            }
            else
            {
                $base_url = \Zend_Controller_Front::getInstance()->getBaseUrl();
                return self::domain(TRUE) . $base_url;
            }
        }
    }

    public static function setBaseUrl($new_base_url)
    {
        self::$base_url = $new_base_url;

        $router = self::getRouter();
        $front = $router->getFrontController();

        $front->setBaseUrl($new_base_url);
    }
    
    public static function content($file_name = NULL)
    {
        if (defined('DF_URL_STATIC'))
            $static_url_base = DF_URL_STATIC;
        else
            $static_url_base = self::baseUrl().'/static';
        
        if ($file_name !== NULL)
            return $static_url_base.'/'.$file_name;
        else
            return $static_url_base;
    }
    
    public static function file($file_name = NULL)
    {
        if (defined('DF_UPLOAD_URL'))
        {
            $static_url_base = self::baseUrl().DF_UPLOAD_URL;
        
            if ($file_name !== NULL)
                return $static_url_base.'/'.$file_name;
            else
                return $static_url_base;
        }
        else
        {
            return self::content($file_name);
        }
    }
    
    public static function cdn($library_name, $library_version)
    {
        $cdn_base = '//ajax.googleapis.com/ajax/libs';
        switch($library_name)
        {
            case 'jquery':
                return $cdn_base.'/jquery/'.$library_version.'/jquery.min.js';
            break;
            
            case 'jqueryui':
                return $cdn_base.'/jqueryui/'.$library_version.'/jquery-ui.min.js';
            break;
        }
    }

    public static function domain($includeScheme = false)
    {
        $domain = $_SERVER['HTTP_HOST'];
        if($includeScheme)
            $domain = 'http'.((DF_IS_SECURE) ? 's' : '').'://'.$domain;

        return $domain;
    }

    /**
     * Returns the referring URL, or, if no referring url, return the default
     * url set (by default "false").
     *
     * @param string $default
     * @return string
     */
    public static function referrer($default = false)
    {
        if( isset($_SERVER['HTTP_REFERER']) )
            return $_SERVER['HTTP_REFERER'];
        else
            return $default;
    }

    public static function current($includeSchemeDomain = TRUE, $include_request_uri = TRUE)
    {
        $prefix = '';
        if($includeSchemeDomain)
        {
            $prefix = 'http' . (DF_IS_SECURE ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
        }
        
        $uri = '';
        if (isset($_SERVER['REQUEST_URI']))
        {
            $uri = $_SERVER['REQUEST_URI'];
        }
        else
        {
            $uri = self::route($request->getParams()).self::arrayToGetString($_GET);
        }
        
        if (!$include_request_uri && strstr($uri, '?') !== FALSE)
        {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        
        return $prefix.$uri;
    }

    /**
     * Generate a URL based on a route
     *
     * @param array $options variables to pass to the router
     * @param string $route which route to process
     * @param boolean $reset reset automatic variable assignment
     * @param boolean $encode url_encode() all pieces of the url
     * @param array $get array of values for a ?get=string to be appended to the URL
     * @return string Generated URL
     */
    public static function route(array $options = array(), $route = null, $reset = true, $encode = true, array $get = array())
    {
        $target = '';
        if (isset($options['#target']))
        {
            $target = '#'.str_replace('#', '', $options['#target']);
            unset($options['#target']);
        }
        
        $justice_friends = self::getRouter();
        return $justice_friends->assemble($options, $route, $reset, $encode).self::arrayToGetString($get).$target;
    }

    /**
     * @return \Zend_Controller_Router_Interface|\Zend_Controller_Router_Rewrite
     * @throws \Zend_Controller_Exception
     * @throws \Zend_Exception
     */
    public static function getRouter()
    {
        static $router;
        
        if (!$router)
        {
            $front = \Zend_Controller_Front::getInstance();
            
            $request = $front->getRequest();
            if (!$request)
            {
                $request = new \Zend_Controller_Request_Http;
                $front->setRequest($request);
            }
            
            $config = \Zend_Registry::get('config');
            if ($config->application->base_url)
                $request->setBaseUrl($config->application->base_url);
            
            $router = $front->getRouter();
            if (!$router)
            {
                $router = new \Zend_Controller_Router_Rewrite;
                $front->setRouter($router);
            }
            
            $router->addDefaultRoutes();
        }
        
        return $router;
    }
    
    // Route to a URL without resetting the current routing path.
    public static function routeFromHere($options = array())
    {
        $options = (is_array($options)) ? $options : array('action' => $options);
        return self::route($options, NULL, FALSE);
    }

    protected static function arrayToGetString(array $get, $preserve_existing_get = false)
    {
        $get_string = array();

        if($preserve_existing_get === true)
        {
            foreach( (array)$_GET as $key => $value )
            {
                $get_string[$key] = urlencode($key) . '=' . urlencode($value);
            }
        }

        foreach( (array)$get as $key => $value )
        {
            $get_string[$key] = urlencode($key) . '=' . urlencode($value);
        }

        if(count($get_string) > 0)
            $get_string = '?' . implode('&', $get_string);
        else
            $get_string = '';

        return $get_string;
    }
}