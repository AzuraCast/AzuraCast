<?php
use \Phalcon\Mvc\Application;

error_reporting(E_ALL);

require __DIR__ . '/../app/bootstrap.php';

$application = new Application($di);
$application->registerModules($phalcon_modules);

echo $application->handle()->getContent();