<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

$di = require dirname(__FILE__).'/../app/bootstrap.php';

// Load app, to generate routes, etc.
$di->get('app');

/** @var \Doctrine\ORM\EntityManager $em */
$em = $di['em'];
$db = $em->getConnection();

$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($db),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em),
    'dialog' => new \Symfony\Component\Console\Helper\QuestionHelper(),
));

$settings = $di['app_settings'];

$cli = new \Symfony\Component\Console\Application($settings['name'].' Command Line Tools', \AzuraCast\Version::getVersion());
$cli->setCatchExceptions(true);
$cli->setHelperSet($helperSet);

\Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($cli);

// Migrations commands
$migrate_config = new \Doctrine\DBAL\Migrations\Configuration\Configuration($db);
$migrate_config->setMigrationsTableName('app_migrations');
$migrate_config->setMigrationsDirectory(__DIR__.'/../app/models/Migration');
$migrate_config->setMigrationsNamespace('Migration');

$migration_commands = [
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand()
];

foreach($migration_commands as $cmd)
    $cmd->setMigrationConfiguration($migrate_config);

$cli->addCommands($migration_commands);

// App-specific commands
$cli->addCommands([
    new \AzuraCast\Console\Command\ClearCache($di),
    new \AzuraCast\Console\Command\RestartRadio($di),
    new \AzuraCast\Console\Command\Sync($di),
    new \AzuraCast\Console\Command\StreamerAuth($di),
    new \AzuraCast\Console\Command\NextSong($di),
    new \AzuraCast\Console\Command\ReprocessMedia($di),
    new \AzuraCast\Console\Command\GenerateApiDocs($di),
    new \AzuraCast\Console\Command\UptimeWait($di),
    new \AzuraCast\Console\Command\MigrateConfig($di),
]);

$cli->run();