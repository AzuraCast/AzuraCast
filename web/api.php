<?php
// Force API URL
if (substr($_SERVER['REQUEST_URI'], 0, 4) !== '/api')
    $_SERVER['REQUEST_URI'] = '/api/'.ltrim($_SERVER['REQUEST_URI'], '/');

define('DF_SITE', 'api');
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
