<?php

declare(strict_types=1);

use App\CallableEventDispatcherInterface;
use App\Environment;
use App\Event;
use App\Middleware;

return static function (CallableEventDispatcherInterface $dispatcher) {
    $dispatcher->addListener(
        Event\BuildConsoleCommands::class,
        function (Event\BuildConsoleCommands $event) {
            call_user_func(include(__DIR__ . '/cli.php'), $event);
        }
    );

    $dispatcher->addListener(
        Event\BuildRoutes::class,
        function (Event\BuildRoutes $event) {
            $app = $event->getApp();

            // Load app-specific route configuration.
            $container = $event->getContainer();

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

            // System middleware for routing and body parsing.
            $app->addBodyParsingMiddleware();
            $app->addRoutingMiddleware();

            // Redirects and updates that should happen before system middleware.
            $app->add(new Middleware\Cache\SetDefaultCache());
            $app->add(new Middleware\RemoveSlashes());
            $app->add(new Middleware\ApplyXForwarded());

            // Add an error handler for most in-controller/task situations.
            $errorMiddleware = $app->addErrorMiddleware(
                $environment->showDetailedErrors(),
                true,
                true,
                $container->get(Psr\Log\LoggerInterface::class)
            );
            $errorMiddleware->setDefaultErrorHandler(Slim\Interfaces\ErrorHandlerInterface::class);
        }
    );

    // Build default menus
    $dispatcher->addListener(
        App\Event\GetSyncTasks::class,
        function (App\Event\GetSyncTasks $e) {
            $e->addTasks([
                App\Sync\Task\CheckFolderPlaylistsTask::class,
                App\Sync\Task\CheckMediaTask::class,
                App\Sync\Task\CheckPodcastPlaylistsTask::class,
                App\Sync\Task\CheckRequestsTask::class,
                App\Sync\Task\CheckUpdatesTask::class,
                App\Sync\Task\CleanupHistoryTask::class,
                App\Sync\Task\CleanupLoginTokensTask::class,
                App\Sync\Task\CleanupRelaysTask::class,
                App\Sync\Task\CleanupStorageTask::class,
                App\Sync\Task\EnforceBroadcastTimesTask::class,
                App\Sync\Task\MoveBroadcastsTask::class,
                App\Sync\Task\QueueInterruptingTracks::class,
                App\Sync\Task\ReactivateStreamerTask::class,
                App\Sync\Task\RenewAcmeCertTask::class,
                App\Sync\Task\RotateLogsTask::class,
                App\Sync\Task\RunAnalyticsTask::class,
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
        Event\GetNotifications::class,
        App\Notification\Check\DonateAdvisorCheck::class
    );
    $dispatcher->addCallableListener(
        Event\GetNotifications::class,
        App\Notification\Check\ServiceCheck::class
    );

    $dispatcher->addCallableListener(
        Event\Media\GetAlbumArt::class,
        App\Media\AlbumArtHandler\LastFmAlbumArtHandler::class,
        priority: 10
    );
    $dispatcher->addCallableListener(
        Event\Media\GetAlbumArt::class,
        App\Media\AlbumArtHandler\MusicBrainzAlbumArtHandler::class,
        priority: -10
    );

    $dispatcher->addCallableListener(
        Event\Media\ReadMetadata::class,
        App\Media\Metadata\Reader\PhpReader::class,
    );
    $dispatcher->addCallableListener(
        Event\Media\ReadMetadata::class,
        App\Media\Metadata\Reader\FfprobeReader::class,
        priority: -10
    );
    $dispatcher->addCallableListener(
        Event\Media\WriteMetadata::class,
        App\Media\Metadata\Writer::class
    );

    $dispatcher->addServiceSubscriber(
        [
            App\Console\ErrorHandler::class,
            App\Nginx\ConfigWriter::class,
            App\Radio\AutoDJ\QueueBuilder::class,
            App\Radio\AutoDJ\Annotations::class,
            App\Radio\Backend\Liquidsoap\ConfigWriter::class,
            App\Radio\Backend\Liquidsoap\PlaylistFileWriter::class,
            App\Sync\NowPlaying\Task\NowPlayingTask::class,
        ]
    );
};
