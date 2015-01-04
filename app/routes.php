<?php
use Phalcon\Mvc\Router;

$router = new Router(false);
$router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

$router->setDefaultModule("frontend");
$router->setDefaultController("index");
$router->setDefaultAction("index");

$router->add('/', array(
    'module' => 'frontend',
    'controller' => 'index',
    'action' => 'index'
));

$router->add('/:module/:controller/:action/:params', array(
    'module' => 1,
    'controller' => 2,
    'action' => 3,
    'params' => 4
));
$router->add('/:controller/:action/:params', array(
    'controller' => 1,
    'action' => 2,
    'params' => 3
));
$router->add('/:action/:params', array(
    'action' => 1,
    'params' => 2
));
$router->add('/:action', array(
    'action' => 1
));

return $router;