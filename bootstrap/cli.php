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

$helperSet = \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($em);
$helperSet->set(new \Symfony\Component\Console\Helper\QuestionHelper, 'dialog');

$cli = new \App\Console\Application($settings['name'].' Command Line Tools ('.APP_APPLICATION_ENV.')', \App\Version::getVersion());
$cli->setContainer($di);
$cli->setCatchExceptions(true);
$cli->setHelperSet($helperSet);

// Doctrine ORM/DBAL
\Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($cli);

// Doctrine Migrations
$migrate_config = new \Doctrine\DBAL\Migrations\Configuration\Configuration($em->getConnection());
$migrate_config->setMigrationsTableName('app_migrations');
$migrate_config->setMigrationsDirectory(dirname(__DIR__).'/src/Entity/Migration');
$migrate_config->setMigrationsNamespace('Entity\Migration');

$output = new \Symfony\Component\Console\Output\ConsoleOutput;
$migrate_config->setOutputWriter(new \Doctrine\DBAL\Migrations\OutputWriter(function($message) use ($output) {
    $output->writeln($message);
}));

$migration_commands = [
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand,
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand,
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand,
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand,
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand,
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand
];

foreach($migration_commands as $cmd) {
    $cmd->setMigrationConfiguration($migrate_config);
    $cli->add($cmd);
}

$cli->registerAppCommands();

return $cli;
