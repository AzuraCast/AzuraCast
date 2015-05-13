<?php
use DF\Phalcon\Application;

// Force API URL
$uri = $_SERVER['REQUEST_URI'];
if (substr($uri, 0, 4) !== '/api')
    $_SERVER['REQUEST_URI'] = '/api'.$uri;


error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

require __DIR__ . '/../app/bootstrap.php';

try
{
    $application = new \DF\Phalcon\Application($di);
    $application->useImplicitView(true);

    $application->bootstrap()->run();
}
catch(\Exception $e)
{
    \DF\Phalcon\ErrorHandler::handle($e, $di);
}
