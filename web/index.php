<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

$autoloader = require dirname(__DIR__).'/vendor/autoload.php';

$app = \App\App::create([
    'autoloader' => $autoloader,
    'settings' => [
        \Azura\Settings::BASE_DIR => dirname(__DIR__),
    ],
]);

$app->run();
