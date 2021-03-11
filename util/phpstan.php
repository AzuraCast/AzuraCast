<?php
/**
 * PHPStan Bootstrap File
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

$autoloader = require dirname(__DIR__) . '/vendor/autoload.php';

App\AppFactory::createCli(
    $autoloader,
    [
        App\Environment::BASE_DIR => dirname(__DIR__),
    ]
);
