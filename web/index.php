<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

$di = require __DIR__ . '/../app/bootstrap.php';

/** @var \Slim\App $app */
$app = $di['app'];

$app->run();