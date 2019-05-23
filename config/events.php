<?php
use App\Middleware;
use App\Console\Command;

return function (\Azura\EventDispatcher $dispatcher)
{
    // Build default routes and middleware
    $dispatcher->addListener(Azura\Event\BuildRoutes::NAME, function(Azura\Event\BuildRoutes $event) {
        $app = $event->getApp();

        if (file_exists(__DIR__.'/routes.dev.php')) {
            $dev_routes = require __DIR__.'/routes.dev.php';
            $dev_routes($app);
        }

        // Get the current user entity object and assign it into the request if it exists.
        $app->add(Middleware\GetCurrentUser::class);

        // Check HTTPS setting and enforce Content Security Policy accordingly.
        $app->add(Middleware\EnforceSecurity::class);

    }, 2);

    // Build default menus
    $dispatcher->addListener(App\Event\BuildAdminMenu::NAME, function(\App\Event\BuildAdminMenu $e) {
        $callable = require(__DIR__.'/menus/admin.php');
        $callable($e);
    });

    $dispatcher->addListener(App\Event\BuildStationMenu::NAME, function(\App\Event\BuildStationMenu $e) {
        $callable = require(__DIR__.'/menus/station.php');
        $callable($e);
    });

    // Build CLI commands
    $dispatcher->addListener(Azura\Event\BuildConsoleCommands::NAME, function(Azura\Event\BuildConsoleCommands $event) {
        $event->getConsole()->addCommands([
            // Liquidsoap Internal CLI Commands
            new Command\Internal\NextSong,
            new Command\Internal\DjAuth,
            new Command\Internal\DjOn,
            new Command\Internal\DjOff,
            new Command\Internal\Feedback,

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
            new Command\Backup,
            new Command\Restore,
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
