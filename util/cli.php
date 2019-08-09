<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

$autoloader = require dirname(__DIR__).'/vendor/autoload.php';

// Placeholder locale functions
\App\Customization::setGlobalValues();

$translator = new \Gettext\Translator();
$translator->register();

$app = \App\AppFactory::create($autoloader, [
    \Azura\Settings::BASE_DIR => dirname(__DIR__),
]);

$di = $app->getContainer();

/** @var \Azura\Console\Application $cli */
$cli = $di->get(\Azura\Console\Application::class);
$cli->run();
