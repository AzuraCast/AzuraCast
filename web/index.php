<?php
use DF\Phalcon\Application;

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
