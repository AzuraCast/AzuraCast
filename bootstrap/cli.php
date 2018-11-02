<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

$di = require __DIR__.'/app.php';

// Load app, to generate routes, etc.
$di->get('app');

// Placeholder locale functions
$translator = new \Gettext\Translator();
$translator->register();

/** @var \Doctrine\ORM\EntityManager $em */
$em = $di[\Doctrine\ORM\EntityManager::class];

/** @var \App\Version $version */
$version = $di[\App\Version::class];

$helperSet = \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($em);
$helperSet->set(new \Symfony\Component\Console\Helper\QuestionHelper, 'dialog');

$cli = new \App\Console\Application($settings['name'].' Command Line Tools ('.APP_APPLICATION_ENV.')', $version->getVersion());
$cli->setContainer($di);
$cli->setHelperSet($helperSet);

return $cli;
