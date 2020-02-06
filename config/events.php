<?php

use App\Console\Command;
use App\Event;
use App\Middleware;
use App\Settings;

return function (\App\EventDispatcher $dispatcher) {
    $dispatcher->addListener(Event\BuildConsoleCommands::class, function (Event\BuildConsoleCommands $event) {
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
            $defaults = [
                'table_name' => 'app_migrations',
                'directory' => $settings[Settings::BASE_DIR] . '/src/Entity/Migration',
                'namespace' => 'App\Entity\Migration',
            ];

            $user_options = $settings[Settings::DOCTRINE_OPTIONS]['migrations'] ?? [];
            $options = array_merge($defaults, $user_options);

            /** @var Doctrine\ORM\EntityManager $em */
            $em = $di->get(Doctrine\ORM\EntityManager::class);
            $connection = $em->getConnection();

            $helper_set = $console->getHelperSet();
            $doctrine_helpers = Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($em);

            $helper_set->set($doctrine_helpers->get('db'), 'db');
            $helper_set->set($doctrine_helpers->get('em'), 'em');

            $migrate_config = new Doctrine\Migrations\Configuration\Configuration($connection);
            $migrate_config->setMigrationsTableName($options['table_name']);
            $migrate_config->setMigrationsDirectory($options['directory']);
            $migrate_config->setMigrationsNamespace($options['namespace']);

            $migrate_config_helper = new Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelper($connection,
                $migrate_config);
            $helper_set->set($migrate_config_helper, 'configuration');

            Doctrine\Migrations\Tools\Console\ConsoleRunner::addCommands($console);
        }

        if (file_exists(__DIR__ . '/cli.php')) {
            call_user_func(include(__DIR__ . '/cli.php'), $console);
        }
    });

    $dispatcher->addListener(Event\BuildRoutes::class, function (Event\BuildRoutes $event) {
        $app = $event->getApp();

        // Load app-specific route configuration.
        $container = $app->getContainer();

        /** @var Settings $settings */
        $settings = $container->get(Settings::class);

        if (file_exists(__DIR__ . '/routes.php')) {
            call_user_func(include(__DIR__ . '/routes.php'), $app);
        }

        if (file_exists(__DIR__ . '/routes.dev.php')) {
            $dev_routes = require __DIR__ . '/routes.dev.php';
            $dev_routes($app);
        }

        $app->add(Middleware\EnforceSecurity::class);
        $app->add(Middleware\InjectAcl::class);
        $app->add(Middleware\GetCurrentUser::class);

        // Request injection middlewares.
        $app->add(Middleware\InjectRouter::class);
        $app->add(Middleware\InjectRateLimit::class);

        // System middleware for routing and body parsing.
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();

        // Redirects and updates that should happen before system middleware.
        $app->add(new Middleware\RemoveSlashes);
        $app->add(new Middleware\ApplyXForwardedProto);

        // Error handling, which should always be near the "last" element.
        $errorMiddleware = $app->addErrorMiddleware(!$settings->isProduction(), true, true);
        $errorMiddleware->setDefaultErrorHandler(Slim\Interfaces\ErrorHandlerInterface::class);

        // Use PSR-7 compatible sessions.
        $app->add(Middleware\InjectSession::class);
    });

    // Build default menus
    $dispatcher->addListener(App\Event\BuildAdminMenu::class, function (\App\Event\BuildAdminMenu $e) {
        $callable = require(__DIR__ . '/menus/admin.php');
        $callable($e);
    });

    $dispatcher->addListener(App\Event\BuildStationMenu::class, function (\App\Event\BuildStationMenu $e) {
        $callable = require(__DIR__ . '/menus/station.php');
        $callable($e);
    });

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
