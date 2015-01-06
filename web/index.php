<?php
use \Phalcon\Mvc\Application;

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

require __DIR__ . '/../app/bootstrap.php';

try
{
    $application = new Application($di);
    $application->registerModules($phalcon_modules);

    echo $application->handle()->getContent();
}
catch(\Exception $e)
{
    \DF\Phalcon\ErrorHandler::handle($e, $di);
}
