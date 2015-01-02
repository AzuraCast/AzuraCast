<?php
use Phalcon\Mvc\Application;

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {

    $di = require(__DIR__ . '/../app/bootstrap.php');
    $application = new Application($di);

    echo $application->handle()->getContent();

} catch (Exception $e) {

    echo $e->getMessage();

}
