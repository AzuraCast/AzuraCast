<?php
use Phalcon\Mvc\Application;

error_reporting(E_ALL);
ini_set('display_errors', 1);

require(__DIR__ . '/../app/bootstrap.php');

$application = new Application($di);
$application->registerModules($phalcon_modules);

echo $application->handle()->getContent();