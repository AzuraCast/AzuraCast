<?php

declare(strict_types=1);

use App\AppFactory;
use App\Environment;

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', '1');

require dirname(__DIR__) . '/vendor/autoload.php';

$app = AppFactory::createApp(
    [
        Environment::BASE_DIR => dirname(__DIR__),
    ]
);

$app->run();
