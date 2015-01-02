<?php
$router = new \Phalcon\Mvc\Router(true);



$router->add('/admin', array(
    'module' => 'backend',
    'controller' => 'index',
    'action' => 'index'
));
$router->add('/index', array(
    'module' => 'frontend',
    'controller' => 'index',
    'action' => 'index'
));

$router->add('/', array(
    'module' => 'frontend',
    'controller' => 'index',
    'action' => 'index'
));

return $router;