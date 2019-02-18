<?php
use App\Middleware;
use App\Console\Command;

return function (\Azura\EventDispatcher $dispatcher)
{
    // Build default routes and middleware
    $dispatcher->addListener(Azura\Event\BuildRoutes::NAME, function(Azura\Event\BuildRoutes $event) {
        $app = $event->getApp();

        // Get the current user entity object and assign it into the request if it exists.
        $app->add(Middleware\GetCurrentUser::class);

        // Check HTTPS setting and enforce Content Security Policy accordingly.
        $app->add(Middleware\EnforceSecurity::class);

    }, 2);

    // Build CLI commands
    $dispatcher->addListener(Azura\Event\BuildConsoleCommands::NAME, function(Azura\Event\BuildConsoleCommands $event) {
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
            new Command\RestartRadio,
            new Command\Sync,
            new Command\ProcessMessageQueue,
            new Command\ReprocessMedia,

            new Command\GenerateApiDocs,
            new Command\UptimeWait,

            // User-side tools
            new Command\ResetPassword,
            new Command\SetAdministrator,
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
        \App\Notification\Manager::class,
    ]);

};
