<?php
/**
 * PHP-DI Services
 */

use App\Settings;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;

return array_merge(
    include(__DIR__ . '/services/cache.php'),
    include(__DIR__ . '/services/database.php'),
    include(__DIR__ . '/services/http.php'),
    include(__DIR__ . '/services/view.php'), [

    // Configuration management
    App\Config::class => function (App\Settings $settings) {
        return new App\Config($settings[App\Settings::CONFIG_DIR]);
    },

    // Console
    App\Console\Application::class => function (DI\Container $di, App\EventDispatcher $dispatcher) {
        $console = new App\Console\Application('Command Line Interface', '1.0.0', $di);

        // Trigger an event for the core app and all plugins to build their CLI commands.
        $event = new App\Event\BuildConsoleCommands($console);
        $dispatcher->dispatch($event);

        return $console;
    },

    // Event Dispatcher
    App\EventDispatcher::class => function (Slim\App $app, App\Plugins $plugins) {
        $dispatcher = new App\EventDispatcher($app->getCallableResolver());

        // Register application default events.
        if (file_exists(__DIR__ . '/events.php')) {
            call_user_func(include(__DIR__ . '/events.php'), $dispatcher);
        }

        // Register plugin-provided events.
        $plugins->registerEvents($dispatcher);

        return $dispatcher;
    },

    // Monolog Logger
    Monolog\Logger::class => function (App\Settings $settings) {
        $logger = new Monolog\Logger($settings[App\Settings::APP_NAME] ?? 'app');
        $logging_level = $settings->isProduction() ? Psr\Log\LogLevel::INFO : Psr\Log\LogLevel::DEBUG;

        if ($settings[App\Settings::IS_DOCKER] || $settings[App\Settings::IS_CLI]) {
            $log_stderr = new Monolog\Handler\StreamHandler('php://stderr', $logging_level, true);
            $logger->pushHandler($log_stderr);
        }

        $log_file = new Monolog\Handler\StreamHandler($settings[App\Settings::TEMP_DIR] . '/app.log',
            $logging_level, true);
        $logger->pushHandler($log_file);

        return $logger;
    },
    Psr\Log\LoggerInterface::class => DI\get(Monolog\Logger::class),

    // Middleware
    App\Middleware\InjectRateLimit::class => DI\autowire(),
    App\Middleware\InjectRouter::class => DI\autowire(),
    App\Middleware\InjectSession::class => DI\autowire(),
    App\Middleware\EnableView::class => DI\autowire(),

    // Rate limiter
    App\RateLimit::class => DI\autowire(),

    // Doctrine annotations reader
    Doctrine\Common\Annotations\Reader::class => function (
        Doctrine\Common\Cache\Cache $doctrine_cache,
        App\Settings $settings
    ) {
        return new Doctrine\Common\Annotations\CachedReader(
            new Doctrine\Common\Annotations\AnnotationReader,
            $doctrine_cache,
            !$settings->isProduction()
        );
    },

    // Symfony Serializer
    Symfony\Component\Serializer\Serializer::class => function (
        Doctrine\Common\Annotations\Reader $annotation_reader,
        Doctrine\ORM\EntityManager $em
    ) {
        $meta_factory = new Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory(
            new Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader($annotation_reader)
        );

        $normalizers = [
            new Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer(),
            new App\Normalizer\DoctrineEntityNormalizer($em, $annotation_reader, $meta_factory),
            new Symfony\Component\Serializer\Normalizer\ObjectNormalizer($meta_factory),
        ];
        return new Symfony\Component\Serializer\Serializer($normalizers);
    },

    // Symfony Validator
    Symfony\Component\Validator\ConstraintValidatorFactoryInterface::class => DI\autowire(App\Validator\ConstraintValidatorFactory::class),

    Symfony\Component\Validator\Validator\ValidatorInterface::class => function (
        Doctrine\Common\Annotations\Reader $annotation_reader,
        Symfony\Component\Validator\ConstraintValidatorFactoryInterface $cvf
    ) {
        $builder = new Symfony\Component\Validator\ValidatorBuilder();
        $builder->setConstraintValidatorFactory($cvf);
        $builder->enableAnnotationMapping($annotation_reader);
        return $builder->getValidator();
    },

    App\Doctrine\Event\AuditLog::class => DI\autowire(),
    App\Doctrine\Event\StationRequiresRestart::class => DI\autowire(),

    // Repositories
    App\Entity\Repository\ApiKeyRepository::class => DI\autowire(),
    App\Entity\Repository\ListenerRepository::class => DI\autowire(),
    App\Entity\Repository\RoleRepository::class => DI\autowire(),
    App\Entity\Repository\RolePermissionRepository::class => DI\autowire(),
    App\Entity\Repository\SettingsRepository::class => DI\autowire(),
    App\Entity\Repository\SongHistoryRepository::class => DI\autowire(),
    App\Entity\Repository\SongRepository::class => DI\autowire(),
    App\Entity\Repository\StationMediaRepository::class => DI\autowire(),
    App\Entity\Repository\StationMountRepository::class => DI\autowire(),
    App\Entity\Repository\StationPlaylistScheduleRepository::class => DI\autowire(),
    App\Entity\Repository\StationPlaylistMediaRepository::class => DI\autowire(),
    App\Entity\Repository\StationRepository::class => DI\autowire(),
    App\Entity\Repository\StationRequestRepository::class => DI\autowire(),
    App\Entity\Repository\StationStreamerRepository::class => DI\autowire(),
    App\Entity\Repository\UserRepository::class => DI\autowire(),

    /*
     * AzuraCast-specific dependencies
     */

    App\Acl::class => DI\autowire(),
    App\Auth::class => DI\autowire(),
    App\ApiUtilities::class => DI\autowire(),
    App\Customization::class => DI\autowire(),
    App\Version::class => DI\autowire(),
    App\Service\AzuraCastCentral::class => DI\autowire(),
    App\Service\IpGeolocation::class => DI\autowire(),
    App\Service\NChan::class => DI\autowire(),
    App\Service\Sentry::class => DI\autowire(),
    App\Service\SftpGo::class => DI\autowire(),
    App\Validator\Constraints\StationPortCheckerValidator::class => DI\autowire(),

    // Message queue manager class
    App\MessageQueue::class => function (
        Redis $redis,
        ContainerInterface $di,
        Monolog\Logger $logger,
        EntityManager $em
    ) {
        // Build QueueFactory
        $driver = new Bernard\Driver\PhpRedis\Driver($redis);

        $normalizer = new Normalt\Normalizer\AggregateNormalizer([
            new Bernard\Normalizer\EnvelopeNormalizer,
            new Symfony\Component\Serializer\Normalizer\PropertyNormalizer,
        ]);

        $serializer = new Bernard\Serializer($normalizer);

        $queue_factory = new Bernard\QueueFactory\PersistentFactory($driver, $serializer);

        // Event dispatcher
        $dispatcher = new Symfony\Component\EventDispatcher\EventDispatcher;

        // Build Producer
        $producer = new Bernard\Producer($queue_factory, $dispatcher);

        // Build Consumer
        $receivers = require __DIR__ . '/messagequeue.php';
        $router = new Bernard\Router\ReceiverMapRouter($receivers, new Bernard\Router\ContainerReceiverResolver($di));

        $consumer = new Bernard\Consumer($router, $dispatcher);

        $mq = new App\MessageQueue(
            $queue_factory,
            $producer,
            $consumer,
            $logger,
            $em
        );

        $dispatcher->addSubscriber($mq);
        return $mq;
    },

    // InfluxDB
    InfluxDB\Database::class => function (Settings $settings) {
        $opts = [
            'host' => $settings->isDocker() ? 'influxdb' : 'localhost',
            'port' => 8086,
        ];

        $influx = new InfluxDB\Client($opts['host'], $opts['port']);
        return $influx->selectDB('stations');
    },

    // Supervisor manager
    Supervisor\Supervisor::class => function (Settings $settings) {
        $guzzle_client = new GuzzleHttp\Client();
        $client = new fXmlRpc\Client(
            'http://' . ($settings->isDocker() ? 'stations' : '127.0.0.1') . ':9001/RPC2',
            new fXmlRpc\Transport\HttpAdapterTransport(
                new Http\Message\MessageFactory\GuzzleMessageFactory(),
                new Http\Adapter\Guzzle6\Client($guzzle_client)
            )
        );

        $connector = new Supervisor\Connector\XmlRpc($client);
        $supervisor = new Supervisor\Supervisor($connector);

        if (!$supervisor->isConnected()) {
            throw new \App\Exception(sprintf('Could not connect to supervisord.'));
        }

        return $supervisor;
    },

    App\Assets::class => function (App\Config $config, Settings $settings) {
        $libraries = $config->get('assets');

        $versioned_files = [];
        $assets_file = $settings[Settings::BASE_DIR] . '/web/static/assets.json';
        if (file_exists($assets_file)) {
            $versioned_files = json_decode(file_get_contents($assets_file), true, 512, JSON_THROW_ON_ERROR);
        }

        return new App\Assets($libraries, $versioned_files);
    },

    /*
     * Radio Components
     */

    App\Radio\Adapters::class => DI\autowire(),
    App\Radio\AutoDJ::class => DI\autowire(),
    App\Radio\Configuration::class => DI\autowire(),

    App\Radio\Filesystem::class => DI\autowire(),

    App\Radio\Backend\Liquidsoap::class => DI\autowire(),
    App\Radio\Backend\None::class => DI\autowire(),

    App\Radio\Frontend\Icecast::class => DI\autowire(),
    App\Radio\Frontend\Remote::class => DI\autowire(),
    App\Radio\Frontend\SHOUTcast::class => DI\autowire(),

    App\Radio\Remote\AzuraRelay::class => DI\autowire(),
    App\Radio\Remote\Icecast::class => DI\autowire(),
    App\Radio\Remote\SHOUTcast1::class => DI\autowire(),
    App\Radio\Remote\SHOUTcast2::class => DI\autowire(),

    /*
     * Synchronized (Cron) Tasks
     */

    App\Sync\Runner::class => function (
        ContainerInterface $di,
        Monolog\Logger $logger,
        App\Entity\Repository\SettingsRepository $settingsRepo
    ) {
        return new App\Sync\Runner(
            $settingsRepo,
            $logger,
            [
                $di->get(App\Sync\Task\NowPlaying::class),
                $di->get(App\Sync\Task\ReactivateStreamer::class),
            ],
            [ // Every minute tasks
                $di->get(App\Sync\Task\RadioRequests::class),
                $di->get(App\Sync\Task\Backup::class),
                $di->get(App\Sync\Task\RelayCleanup::class),
            ],
            [ // Every 5 minutes tasks
                $di->get(App\Sync\Task\Media::class),
                $di->get(App\Sync\Task\FolderPlaylists::class),
                $di->get(App\Sync\Task\CheckForUpdates::class),
            ],
            [ // Every hour tasks
                $di->get(App\Sync\Task\Analytics::class),
                $di->get(App\Sync\Task\RadioAutomation::class),
                $di->get(App\Sync\Task\HistoryCleanup::class),
                $di->get(App\Sync\Task\RotateLogs::class),
                $di->get(App\Sync\Task\UpdateGeoLiteDatabase::class),
            ]
        );
    },

    App\Sync\Task\Analytics::class => DI\autowire(),
    App\Sync\Task\Backup::class => DI\autowire(),
    App\Sync\Task\CheckForUpdates::class => DI\autowire(),
    App\Sync\Task\FolderPlaylists::class => DI\autowire(),
    App\Sync\Task\HistoryCleanup::class => DI\autowire(),
    App\Sync\Task\Media::class => DI\autowire(),
    App\Sync\Task\ReactivateStreamer::class => DI\autowire(),
    App\Sync\Task\NowPlaying::class => DI\autowire(),
    App\Sync\Task\RadioAutomation::class => DI\autowire(),
    App\Sync\Task\RadioRequests::class => DI\autowire(),
    App\Sync\Task\RelayCleanup::class => DI\autowire(),
    App\Sync\Task\RotateLogs::class => DI\autowire(),
    App\Sync\Task\UpdateGeoLiteDatabase::class => DI\autowire(),

    /**
     * Web Hooks
     */

    App\Webhook\Dispatcher::class => function (
        ContainerInterface $di,
        App\Config $config,
        Monolog\Logger $logger
    ) {
        $webhooks = $config->get('webhooks');
        $services = [];
        foreach ($webhooks['webhooks'] as $webhook_key => $webhook_info) {
            $services[$webhook_key] = $di->get($webhook_info['class']);
        }

        return new App\Webhook\Dispatcher($logger, $services);
    },

    App\Webhook\Connector\Discord::class => DI\autowire(),
    App\Webhook\Connector\Generic::class => DI\autowire(),
    App\Webhook\Connector\Local::class => DI\autowire(),
    App\Webhook\Connector\TuneIn::class => DI\autowire(),
    App\Webhook\Connector\Telegram::class => DI\autowire(),
    App\Webhook\Connector\Twitter::class => DI\autowire(),

    /*
     * Middleware
     */

    App\Middleware\EnforceSecurity::class => DI\autowire(),
    App\Middleware\GetCurrentUser::class => DI\autowire(),
    App\Middleware\GetStation::class => DI\autowire(),
    App\Middleware\InjectAcl::class => DI\autowire(),
    App\Middleware\RequireLogin::class => DI\create(),

    // Module-specific middleware
    App\Middleware\Module\Admin::class => DI\autowire(),
    App\Middleware\Module\Api::class => DI\autowire(),
    App\Middleware\Module\Stations::class => DI\autowire(),
    App\Middleware\Module\StationFiles::class => DI\autowire(),

    /*
     * Notifications
     */

    App\Notification\Manager::class => DI\autowire(),

    /*
     * Class Groups
     */

    'App\Form\*Form' => DI\autowire(),
    'App\Entity\Fixture\*' => DI\autowire(),

    /*
     * Controller Classes
     */

    'App\Controller\Admin\*Controller' => DI\autowire(),

    'App\Controller\Api\*Controller' => DI\autowire(),
    'App\Controller\Api\Admin\*Controller' => DI\autowire(),
    'App\Controller\Api\Stations\*Controller' => DI\autowire(),

    'App\Controller\Frontend\*Controller' => DI\autowire(),

    'App\Controller\Stations\*Controller' => DI\autowire(),
    'App\Controller\Stations\Files\*Controller' => DI\autowire(),
    'App\Controller\Stations\Reports\*Controller' => DI\autowire(),
]);
