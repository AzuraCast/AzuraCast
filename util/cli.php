<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

$di = require dirname(__DIR__).'/app/bootstrap.php';

// Load app, to generate routes, etc.
$di->get('app');

// Placeholder locale functions
$translator = new \Gettext\Translator();
$translator->register();

/** @var \AzuraCast\Console\Application $cli */
$cli = $di[\AzuraCast\Console\Application::class];
$cli->run();