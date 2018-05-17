<?php
namespace AzuraCast\Console;

use App\Console\Command as AppCommand;
use Doctrine\ORM\EntityManager;
use Slim\Container;

class Application extends \App\Console\Application
{
    /**
     * Register all CLI commands and return a ready-to-execute CLI runner.
     *
     * @param Container $di
     * @param $settings
     * @return Application
     */
    public static function create(Container $di, $settings): self
    {
        /** @var EntityManager $em */
        $em = $di[EntityManager::class];

        $helperSet = \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($em);
        $helperSet->set(new \Symfony\Component\Console\Helper\QuestionHelper, 'dialog');

        $cli = new self($settings['name'].' Command Line Tools ('.APP_APPLICATION_ENV.')', \AzuraCast\Version::getVersion());
        $cli->setContainer($di);
        $cli->setCatchExceptions(true);
        $cli->setHelperSet($helperSet);

        // Doctrine ORM/DBAL
        \Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($cli);

        // Doctrine Migrations
        $migrate_config = new \Doctrine\DBAL\Migrations\Configuration\Configuration($em->getConnection());
        $migrate_config->setMigrationsTableName('app_migrations');
        $migrate_config->setMigrationsDirectory(APP_INCLUDE_BASE.'/src/Entity/Migration');
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

        // Liquidsoap Internal CLI Commands
        $cli->addCommands([
            new Command\NextSong,
            new Command\DjAuth,
            new Command\DjOn,
            new Command\DjOff,
        ]);

        // Other App-specific Commands
        $cli->addCommands([
            // Locales
            new AppCommand\LocaleGenerate,
            new AppCommand\LocaleImport,

            // Setup
            new Command\MigrateConfig,
            new Command\SetupInflux,
            new Command\SetupFixtures,
            new Command\Setup,

            // Maintenance
            new Command\ClearCache,
            new Command\RestartRadio,
            new Command\Sync,
            new Command\ReprocessMedia,

            new Command\GenerateApiDocs,
            new Command\UptimeWait,

            // User-side tools
            new Command\ResetPassword,
        ]);

        return $cli;
    }
}