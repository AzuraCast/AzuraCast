<?php

declare(strict_types=1);

use App\Environment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LogLevel;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = App\AppFactory::createApp(
    [
        App\Environment::LOG_LEVEL => LogLevel::DEBUG,
    ]
);
$di = $app->getContainer();

$env = $di->get(Environment::class);
App\Enums\SupportedLocales::createForCli($env);

$em = $di->get(EntityManagerInterface::class);
