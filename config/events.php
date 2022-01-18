<?php

use App\Environment;
use App\Event;
use App\Middleware;
use Azura\SlimCallableEventDispatcher\CallableEventDispatcherInterface;

return function (CallableEventDispatcherInterface $dispatcher) {
    $dispatcher->addListener(
        Event\BuildConsoleCommands::class,
        function (Event\BuildConsoleCommands $event) use ($dispatcher) {
            $console = $event->getConsole();
            $di = $event->getContainer();

            // Doctrine ORM/DBAL
            Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($console);

            // Add Doctrine Migrations
            /** @var Doctrine\ORM\EntityManagerInterface $em */
            $em = $di->get(Doctrine\ORM\EntityManagerInterface::class);

            /** @var Environment $environment */
            $environment = $di->get(Environment::class);

            $helper_set = $console->getHelperSet();
            $doctrine_helpers = Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($em);
            $helper_set->set($doctrine_helpers->get('db'), 'db');
            $helper_set->set($doctrine_helpers->get('em'), 'em');

            $migrationConfigurations = [
                'migrations_paths' => [
                    'App\Entity\Migration' => $environment->getBaseDirectory() . '/src/Entity/Migration',
                ],
                'table_storage' => [
                    'table_name' => 'app_migrations',
                    'version_column_length' => 191,
                ],
            ];

            $buildMigrationConfigurationsEvent = new Event\BuildMigrationConfigurationArray(
                $migrationConfigurations,
                $environment->getBaseDirectory()
            );
            $dispatcher->dispatch($buildMigrationConfigurationsEvent);

            $migrationConfigurations = $buildMigrationConfigurationsEvent->getMigrationConfigurations();

            $migrateConfig = new Doctrine\Migrations\Configuration\Migration\ConfigurationArray(
                $migrationConfigurations
            );

            $migrateFactory = Doctrine\Migrations\DependencyFactory::fromEntityManager(
                $migrateConfig,
                new Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager($em)
            );
            Doctrine\Migrations\Tools\Console\ConsoleRunner::addCommands($console, $migrateFactory);

            call_user_func(include(__DIR__ . '/cli.php'), $event);
        }
    );

    $dispatcher->addListener(
        Event\BuildRoutes::class,
        function (Event\BuildRoutes $event) {
            $app = $event->getApp();

            // Load app-specific route configuration.
            $container = $app->getContainer();

            /** @var Environment $environment */
            $environment = $container->get(Environment::class);

            call_user_func(include(__DIR__ . '/routes.php'), $app);

            if (file_exists(__DIR__ . '/routes.dev.php')) {
                call_user_func(include(__DIR__ . '/routes.dev.php'), $app);
            }

            $app->add(Middleware\WrapExceptionsWithRequestData::class);

            $app->add(Middleware\EnforceSecurity::class);

            // Request injection middlewares.
            $app->add(Middleware\InjectRouter::class);
            $app->add(Middleware\InjectRateLimit::class);

            // Re-establish database connection if multiple requests are handled by the same stack.
            $app->add(Middleware\ReopenEntityManagerMiddleware::class);

            // System middleware for routing and body parsing.
            $app->addBodyParsingMiddleware();
            $app->addRoutingMiddleware();

            // Redirects and updates that should happen before system middleware.
            $app->add(new Middleware\RemoveSlashes);
            $app->add(new Middleware\ApplyXForwardedProto);

            // Use PSR-7 compatible sessions.
            $app->add(Middleware\InjectSession::class);

            // Add an error handler for most in-controller/task situations.
            $errorMiddleware = $app->addErrorMiddleware(
                !$environment->isProduction(),
                true,
                true,
                $container->get(Psr\Log\LoggerInterface::class)
            );
            $errorMiddleware->setDefaultErrorHandler(Slim\Interfaces\ErrorHandlerInterface::class);
        }
    );

    // Build default menus
    $dispatcher->addListener(
        App\Event\BuildAdminMenu::class,
        function (App\Event\BuildAdminMenu $e) {
            call_user_func(include(__DIR__ . '/menus/admin.php'), $e);
        }
    );

    $dispatcher->addListener(
        App\Event\BuildStationMenu::class,
        function (App\Event\BuildStationMenu $e) {
            call_user_func(include(__DIR__ . '/menus/station.php'), $e);
        }
    );

    $dispatcher->addListener(
        App\Event\GetSyncTasks::class,
        function (App\Event\GetSyncTasks $e) {
            $e->addTasks([
                App\Sync\Task\CheckFolderPlaylistsTask::class,
                App\Sync\Task\CheckMediaTask::class,
                App\Sync\Task\CheckRequestsTask::class,
                App\Sync\Task\CheckUpdatesTask::class,
                App\Sync\Task\CleanupHistoryTask::class,
                App\Sync\Task\CleanupLoginTokensTask::class,
                App\Sync\Task\CleanupRelaysTask::class,
                App\Sync\Task\CleanupStorageTask::class,
                App\Sync\Task\MoveBroadcastsTask::class,
                App\Sync\Task\ReactivateStreamerTask::class,
                App\Sync\Task\RotateLogsTask::class,
                App\Sync\Task\RunAnalyticsTask::class,
                App\Sync\Task\RunAutomatedAssignmentTask::class,
                App\Sync\Task\RunBackupTask::class,
                App\Sync\Task\UpdateGeoLiteTask::class,
                App\Sync\Task\UpdateStorageLocationSizesTask::class,
            ]);
        }
    );

    // Other event subscribers from across the application.
    $dispatcher->addCallableListener(
        Event\GetNotifications::class,
        App\Notification\Check\BaseUrlCheck::class
    );
    $dispatcher->addCallableListener(
        Event\GetNotifications::class,
        App\Notification\Check\ComposeVersionCheck::class
    );
    $dispatcher->addCallableListener(
        Event\GetNotifications::class,
        App\Notification\Check\UpdateCheck::class
    );
    $dispatcher->addCallableListener(
        Event\GetNotifications::class,
        App\Notification\Check\RecentBackupCheck::class
    );
    $dispatcher->addCallableListener(
        Event\GetNotifications::class,
        App\Notification\Check\SyncTaskCheck::class
    );
    $dispatcher->addCallableListener(
        Event\GetNotifications::class,
        App\Notification\Check\ProfilerAdvisorCheck::class
    );

    $dispatcher->addCallableListener(
        Event\Media\GetAlbumArt::class,
        App\Media\AlbumArtHandler\LastFmAlbumArtHandler::class,
        '__invoke',
        10
    );
    $dispatcher->addCallableListener(
        Event\Media\GetAlbumArt::class,
        App\Media\AlbumArtHandler\MusicBrainzAlbumArtHandler::class,
        '__invoke',
        -10
    );

    $dispatcher->addServiceSubscriber(
        [
            App\Media\MetadataManager::class,
            App\Console\ErrorHandler::class,
            App\Radio\AutoDJ\Queue::class,
            App\Radio\AutoDJ\Annotations::class,
            App\Radio\Backend\Liquidsoap\ConfigWriter::class,
            App\Sync\NowPlaying\Task\NowPlayingTask::class,
        ]
    );
};
