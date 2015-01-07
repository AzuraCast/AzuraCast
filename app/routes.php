<?php
//Use the annotations router
use DF\Phalcon\Router;

$router = new Router(false);
$router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

$router->setDi($di);

$router_config = $di->get('config')->routes->toArray();

$router->setDefaultModule($router_config['default_module']);
$router->setDefaultController($router_config['default_controller']);
$router->setDefaultAction($router_config['default_action']);
$router->removeExtraSlashes(true);

foreach((array)$router_config['custom_routes'] as $route_path => $route_params)
{
    $route = $router->add($route_path, $route_params);

    if (isset($route_params['name']))
        $route->setName($route_params['name']);
}

return $router;