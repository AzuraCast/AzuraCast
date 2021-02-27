<?php
/**
 * PHPStan Bootstrap File
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

$autoloader = require dirname(__DIR__) . '/vendor/autoload.php';

$app = App\AppFactory::create(
    $autoloader,
    [
        App\Environment::BASE_DIR => dirname(__DIR__),
    ]
);

$di = $app->getContainer();

/** @var \Psr\Container\ContainerInterface|\DI\FactoryInterface $di */
$di = $app->getContainer();

/** @var \App\Locale $locale */
$locale = $di->make(\App\Locale::class);
$locale->register();
