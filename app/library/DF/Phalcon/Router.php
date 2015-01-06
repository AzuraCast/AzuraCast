<?php
namespace DF\Phalcon;

class Router extends \Phalcon\Mvc\Router
{
    const URI_DELIMITER = '/';

    public function handle($path = null)
    {
        if ($path === null)
            $path = $this->getRewriteUri();

        // First attempt regular resolution.
        parent::handle($path);
        $router_route = $this->getMatchedRoute();

        if ($router_route !== NULL)
            return $path;

        $di = $this->getDI();
        $module_list = array_keys($di->get('phalcon_modules'));

        $path = trim($path, self::URI_DELIMITER);

        if ($path != '')
        {
            $path = explode(self::URI_DELIMITER, $path);

            if (in_array($path[0], $module_list))
                $this->_module = array_shift($path);

            if (count($path) && !empty($path[0]))
                $this->_controller = array_shift($path);

            if (count($path) && !empty($path[0]))
                $this->_action = array_shift($path);

            if ($numSegs = count($path))
            {
                for ($i = 0; $i < $numSegs; $i = $i + 2)
                {
                    $key = urldecode($path[$i]);
                    $val = isset($path[$i + 1]) ? urldecode($path[$i + 1]) : null;
                    $this->_params[$key] = (isset($this->_params[$key]) ? (array_merge((array)$this->_params[$key], array($val))): $val);
                }
            }
        }

        return $path;
    }
}