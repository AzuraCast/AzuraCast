<?php

declare(strict_types=1);

use App\Environment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LogLevel;

require dirname(__DIR__) . '/vendor/autoload.php';

$di = App\AppFactory::buildContainer(
    [
        App\Environment::BASE_DIR => dirname(__DIR__),
        App\Environment::LOG_LEVEL => LogLevel::DEBUG,
    ]
);

$app = App\AppFactory::buildAppFromContainer($di);

$env = $di->get(Environment::class);
App\Enums\SupportedLocales::createForCli($env);

$em = $di->get(EntityManagerInterface::class);
