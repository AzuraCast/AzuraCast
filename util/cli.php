<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', 1);

$di = require dirname(__DIR__).'/app/bootstrap.php';

// Load app, to generate routes, etc.
$di->get('app');

// Placeholder locale functions
$translator = new \Gettext\Translator();
$translator->register();

/** @var \Doctrine\ORM\EntityManager $em */
$em = $di[\Doctrine\ORM\EntityManager::class];
$db = $em->getConnection();

$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($db),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em),
    'dialog' => new \Symfony\Component\Console\Helper\QuestionHelper(),
));

$settings = $di['app_settings'];

$cli = new \Symfony\Component\Console\Application($settings['name'].' Command Line Tools ('.APP_APPLICATION_ENV.')', \AzuraCast\Version::getVersion());
$cli->setCatchExceptions(true);
$cli->setHelperSet($helperSet);

\Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($cli);

// Migrations commands
$migrate_config = new \Doctrine\DBAL\Migrations\Configuration\Configuration($db);
$migrate_config->setMigrationsTableName('app_migrations');
$migrate_config->setMigrationsDirectory(__DIR__.'/../app/src/Migration');
$migrate_config->setMigrationsNamespace('Migration');

$output = new \Symfony\Component\Console\Output\ConsoleOutput;
$migrate_config->setOutputWriter(new \Doctrine\DBAL\Migrations\OutputWriter(function($message) use ($output) {
    $output->writeln($message);
}));

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

// Liquidsoap internal commands
$cli->addCommands([
    new \AzuraCast\Console\Command\StreamerAuth($di),
    new \AzuraCast\Console\Command\NextSong($di),
    new \AzuraCast\Console\Command\DjOn($di),
    new \AzuraCast\Console\Command\DjOff($di),
]);

// Other app-specific commands
$cli->addCommands([
    // Locales
    new \App\Console\Command\LocaleGenerate($di),
    new \App\Console\Command\LocaleImport($di),

    // Setup
    new \AzuraCast\Console\Command\MigrateConfig($di),
    new \AzuraCast\Console\Command\SetupInflux($di),

    // Maintenance
    new \AzuraCast\Console\Command\ClearCache($di),
    new \AzuraCast\Console\Command\RestartRadio($di),
    new \AzuraCast\Console\Command\Sync($di),
    new \AzuraCast\Console\Command\ReprocessMedia($di),

    new \AzuraCast\Console\Command\GenerateApiDocs($di),
    new \AzuraCast\Console\Command\UptimeWait($di),

    // User-side tools
    new \AzuraCast\Console\Command\ResetPassword($di),

]);

$cli->run();