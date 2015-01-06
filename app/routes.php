<?php
//Use the annotations router
use DF\Phalcon\Router;

$router = new Router(false);
$router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

$router->setDi($di);

$router->notFound(array(
    'module' => 'frontend',
    'controller' => 'error',
    'action' => 'pagenotfound',
));

$router->setDefaultModule("frontend");
$router->setDefaultController("index");
$router->setDefaultAction("index");
$router->removeExtraSlashes(true);

$router->add('/', array(
    'module' => 'frontend',
    'controller' => 'index',
    'action' => 'index'
));

return $router;