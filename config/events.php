<?php

use App\Console\Command;
use App\Event;
use App\Middleware;
use App\Settings;

return function (App\EventDispatcher $dispatcher) {
    $dispatcher->addListener(Event\BuildConsoleCommands::class,
        function (Event\BuildConsoleCommands $event) use ($dispatcher) {
            $console = $event->getConsole();
            $di = $console->getContainer();

            /** @var Settings $settings */
            $settings = $di->get(Settings::class);

            if ($settings->enableRedis()) {
                $console->command('cache:clear', Command\ClearCacheCommand::class)
                    ->setDescription('Clear all application caches.');
            }

            if ($settings->enableDatabase()) {
                // Doctrine ORM/DBAL
                Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($console);

                // Add Doctrine Migrations
                /** @var Doctrine\ORM\EntityManagerInterface $em */
                $em = $di->get(Doctrine\ORM\EntityManagerInterface::class);

                $helper_set = $console->getHelperSet();
                $doctrine_helpers = Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($em);
                $helper_set->set($doctrine_helpers->get('db'), 'db');
                $helper_set->set($doctrine_helpers->get('em'), 'em');

                $migrationConfigurations = [
                    'migrations_paths' => [
                        'App\Entity\Migration' => $settings[Settings::BASE_DIR] . '/src/Entity/Migration',
                    ],
                    'table_storage' => [
                        'table_name' => 'app_migrations',
                        'version_column_length' => 191,
                    ],
                ];

                $buildMigrationConfigurationsEvent = new Event\BuildMigrationConfigurationArray(
                    $migrationConfigurations,
                    $settings[Settings::BASE_DIR]
                );
                $dispatcher->dispatch($buildMigrationConfigurationsEvent);

                $migrationConfigurations = $buildMigrationConfigurationsEvent->getMigrationConfigurations();

                $migrateConfig = new Doctrine\Migrations\Configuration\Migration\ConfigurationArray($migrationConfigurations);

                $migrateFactory = Doctrine\Migrations\DependencyFactory::fromEntityManager(
                    $migrateConfig,
                    new Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager($em)
                );
                Doctrine\Migrations\Tools\Console\ConsoleRunner::addCommands($console, $migrateFactory);
            }

            call_user_func(include(__DIR__ . '/cli.php'), $console);
        });

    $dispatcher->addListener(Event\BuildRoutes::class, function (Event\BuildRoutes $event) {
        $app = $event->getApp();

        // Load app-specific route configuration.
        $container = $app->getContainer();

        /** @var Settings $settings */
        $settings = $container->get(Settings::class);

        call_user_func(include(__DIR__ . '/routes.php'), $app);

        if (file_exists(__DIR__ . '/routes.dev.php')) {
            call_user_func(include(__DIR__ . '/routes.dev.php'), $app);
        }

        $app->add(Middleware\WrapExceptionsWithRequestData::class);

        $app->add(Middleware\EnforceSecurity::class);
        $app->add(Middleware\InjectAcl::class);
        $app->add(Middleware\GetCurrentUser::class);

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
        $errorMiddleware = $app->addErrorMiddleware(!$settings->isProduction(), true, true);
        $errorMiddleware->setDefaultErrorHandler(Slim\Interfaces\ErrorHandlerInterface::class);
    });

    // Build default menus
    $dispatcher->addListener(App\Event\BuildAdminMenu::class, function (App\Event\BuildAdminMenu $e) {
        call_user_func(include(__DIR__ . '/menus/admin.php'), $e);
    });

    $dispatcher->addListener(App\Event\BuildStationMenu::class, function (App\Event\BuildStationMenu $e) {
        call_user_func(include(__DIR__ . '/menus/station.php'), $e);
    });

    // Other event subscribers from across the application.
    $dispatcher->addCallableListener(
        Event\GetSyncTasks::class,
        App\Sync\TaskLocator::class
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

    $dispatcher->addServiceSubscriber([
        App\Radio\AutoDJ\Queue::class,
        App\Radio\AutoDJ\Annotations::class,
        App\Radio\Backend\Liquidsoap\ConfigWriter::class,
        App\Sync\Task\NowPlaying::class,
        App\Webhook\Dispatcher::class,
        App\Controller\Api\NowplayingController::class,
    ]);

};
