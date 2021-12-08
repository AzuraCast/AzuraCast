<?php

declare(strict_types=1);

use App\Environment;
use App\Locale;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LogLevel;

$autoloader = require dirname(__DIR__) . '/vendor/autoload.php';

$di = \App\AppFactory::buildContainer($autoloader, 
    [
        App\Environment::BASE_DIR => dirname(__DIR__),
        App\Environment::LOG_LEVEL => LogLevel::DEBUG
    ]
);

$app = \App\AppFactory::buildAppFromContainer($di);

$env = $di->get(Environment::class);

$locale = Locale::createForCli($env);
$locale->register();
unset($locale);

$em = $di->get(EntityManagerInterface::class);
