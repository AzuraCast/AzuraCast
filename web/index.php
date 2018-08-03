<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

$di = require dirname(__DIR__) . '/bootstrap/app.php';

/** @var \Slim\App $app */
$app = $di['app'];
$app->run();
