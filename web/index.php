<?php
use App\Phalcon\Application;

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

require __DIR__ . '/../app/bootstrap.php';

try
{
    $application = new \App\Phalcon\Application($di);
    $application->useImplicitView(true);

    $application->bootstrap()->run();
}
catch(\Exception $e)
{
    \App\Phalcon\ErrorHandler::handle($e, $di);
}