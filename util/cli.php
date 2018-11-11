<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

$autoloader = require dirname(__DIR__).'/vendor/autoload.php';

// Placeholder locale functions
$translator = new \Gettext\Translator();
$translator->register();

$app = \App\App::create([
    'autoloader' => $autoloader,
    'settings' => [
        \Azura\Settings::BASE_DIR => dirname(__DIR__),
    ],
]);

$di = $app->getContainer();

/** @var \Azura\Console\Application $cli */
$cli = $di[\Azura\Console\Application::class];

/** @var \App\Version $version */
$version = $di[\App\Version::class];

/** @var \Azura\Settings $settings */
$settings = $di['settings'];

$cli->setName($settings[\Azura\Settings::APP_NAME].' Command Line Tools ('.$settings[\Azura\Settings::APP_ENV].')');
$cli->setVersion($version->getVersion());

$cli->run();
