<?php
use App\Event;
use App\Middleware;
use App\Console\Command;

return function (\App\EventDispatcher $dispatcher)
{
    // Build default routes and middleware
    $dispatcher->addListener(Event\BuildRoutes::NAME, function(Event\BuildRoutes $event) {
        $app = $event->getApp();

        // Get the current user entity object and assign it into the request if it exists.
        $app->add(Middleware\GetCurrentUser::class);

        // Inject the application router into the request object.
        $app->add(Middleware\EnableRouter::class);

        // Inject the session manager into the request object.
        $app->add(Middleware\EnableSession::class);

        // Check HTTPS setting and enforce Content Security Policy accordingly.
        $app->add(Middleware\EnforceSecurity::class);

        // Remove trailing slash from all URLs when routing.
        $app->add(Middleware\RemoveSlashes::class);
    }, 1);

    $dispatcher->addListener(Event\BuildRoutes::NAME, function(Event\BuildRoutes $event) {
        call_user_func(include(__DIR__.'/routes.php'), $event->getApp());
    }, 0);

    // Build CLI commands
    $dispatcher->addListener(Event\BuildConsoleCommands::NAME, function(Event\BuildConsoleCommands $event) {
        $em = $event->getConsole()->getService(\Doctrine\ORM\EntityManager::class);

        // Doctrine ORM/DBAL
        \Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($event->getConsole());

        // Doctrine Migrations
        $migrate_config = new \Doctrine\DBAL\Migrations\Configuration\Configuration($em->getConnection());
        $migrate_config->setMigrationsTableName('app_migrations');
        $migrate_config->setMigrationsDirectory(dirname(__DIR__).'/src/Entity/Migration');
        $migrate_config->setMigrationsNamespace('App\Entity\Migration');

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
            $event->getConsole()->add($cmd);
        }
    }, 1);

    $dispatcher->addListener(Event\BuildConsoleCommands::NAME, function(Event\BuildConsoleCommands $event) {
        $event->getConsole()->addCommands([
            // Liquidsoap Internal CLI Commands
            new Command\NextSong,
            new Command\DjAuth,
            new Command\DjOn,
            new Command\DjOff,

            // Locales
            new Command\LocaleGenerate,
            new Command\LocaleImport,

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
            new Command\ListSettings,
            new Command\SetSetting,
        ]);
    }, 0);

    // Other event subscribers from across the application.
    $dispatcher->addServiceSubscriber([
        \App\Radio\AutoDJ::class,
        \App\Radio\Backend\Liquidsoap::class,
        \App\Sync\Task\NowPlaying::class,
        \App\Webhook\Dispatcher::class,
        \App\Controller\Api\NowplayingController::class,
    ]);

};
