<?php
/**
 * PHP-DI Services
 */

use App\Event;
use App\Settings;
use Psr\Container\ContainerInterface;

return [

    // URL Router helper
    App\Http\Router::class => function (
        Settings $settings,
        Slim\App $app,
        App\Entity\Repository\SettingsRepository $settingsRepo
    ) {
        $route_parser = $app->getRouteCollector()->getRouteParser();
        return new App\Http\Router($settings, $route_parser, $settingsRepo);
    },
    App\Http\RouterInterface::class => DI\Get(App\Http\Router::class),

    // Error handler
    App\Http\ErrorHandler::class => DI\autowire(),
    Slim\Interfaces\ErrorHandlerInterface::class => DI\Get(App\Http\ErrorHandler::class),

    // HTTP client
    GuzzleHttp\Client::class => function (Psr\Log\LoggerInterface $logger) {
        $stack = GuzzleHttp\HandlerStack::create();

        $stack->unshift(function (callable $handler) {
            return function (Psr\Http\Message\RequestInterface $request, array $options) use ($handler) {
                $options[GuzzleHttp\RequestOptions::VERIFY] = Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
                return $handler($request, $options);
            };
        }, 'ssl_verify');

        $stack->push(GuzzleHttp\Middleware::log(
            $logger,
            new GuzzleHttp\MessageFormatter('HTTP client {method} call to {uri} produced response {code}'),
            Psr\Log\LogLevel::DEBUG
        ));

        return new GuzzleHttp\Client([
            'handler' => $stack,
            GuzzleHttp\RequestOptions::HTTP_ERRORS => false,
            GuzzleHttp\RequestOptions::TIMEOUT => 3.0,
        ]);
    },

    // DBAL
    Doctrine\DBAL\Connection::class => function (Doctrine\ORM\EntityManagerInterface $em) {
        return $em->getConnection();
    },

    // Doctrine Entity Manager
    App\Doctrine\DecoratedEntityManager::class => function (
        Doctrine\Common\Cache\Cache $doctrineCache,
        Doctrine\Common\Annotations\Reader $reader,
        Settings $settings,
        App\Doctrine\Event\StationRequiresRestart $eventRequiresRestart,
        App\Doctrine\Event\AuditLog $eventAuditLog,
        App\EventDispatcher $dispatcher
    ) {
        $connectionOptions = [
            'host' => $_ENV['MYSQL_HOST'] ?? 'mariadb',
            'port' => $_ENV['MYSQL_PORT'] ?? 3306,
            'dbname' => $_ENV['MYSQL_DATABASE'],
            'user' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASSWORD'],
            'driver' => 'pdo_mysql',
            'charset' => 'utf8mb4',
            'defaultTableOptions' => [
                'charset' => 'utf8mb4',
                'collate' => 'utf8mb4_general_ci',
            ],
            'driverOptions' => [
                // PDO::MYSQL_ATTR_INIT_COMMAND = 1002;
                1002 => 'SET NAMES utf8mb4 COLLATE utf8mb4_general_ci',
            ],
            'platform' => new Doctrine\DBAL\Platforms\MariaDb1027Platform(),
        ];

        if (!$settings[Settings::IS_DOCKER]) {
            $connectionOptions['host'] = $_ENV['db_host'] ?? 'localhost';
            $connectionOptions['port'] = $_ENV['db_port'] ?? '3306';
            $connectionOptions['dbname'] = $_ENV['db_name'] ?? 'azuracast';
            $connectionOptions['user'] = $_ENV['db_username'] ?? 'azuracast';
            $connectionOptions['password'] = $_ENV['db_password'];
        }

        try {
            // Fetch and store entity manager.
            $config = Doctrine\ORM\Tools\Setup::createConfiguration(
                Doctrine\Common\Proxy\AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS,
                $settings->getTempDirectory() . '/proxies',
                $doctrineCache
            );

            $mappingClassesPaths = [$settings->getBaseDirectory() . '/src/Entity'];

            $buildDoctrineMappingPathsEvent = new Event\BuildDoctrineMappingPaths(
                $mappingClassesPaths,
                $settings->getBaseDirectory()
            );
            $dispatcher->dispatch($buildDoctrineMappingPathsEvent);

            $mappingClassesPaths = $buildDoctrineMappingPathsEvent->getMappingClassesPaths();

            $annotationDriver = new Doctrine\ORM\Mapping\Driver\AnnotationDriver(
                $reader,
                $mappingClassesPaths
            );
            $config->setMetadataDriverImpl($annotationDriver);

            // Debug mode:
            // $config->setSQLLogger(new Doctrine\DBAL\Logging\EchoSQLLogger);

            $config->addCustomNumericFunction('RAND', DoctrineExtensions\Query\Mysql\Rand::class);

            if (!Doctrine\DBAL\Types\Type::hasType('carbon_immutable')) {
                Doctrine\DBAL\Types\Type::addType('carbon_immutable', Carbon\Doctrine\CarbonImmutableType::class);
            }

            $eventManager = new Doctrine\Common\EventManager;
            $eventManager->addEventSubscriber($eventRequiresRestart);
            $eventManager->addEventSubscriber($eventAuditLog);

            return new App\Doctrine\DecoratedEntityManager(function () use (
                $connectionOptions,
                $config,
                $eventManager
            ) {
                return Doctrine\ORM\EntityManager::create($connectionOptions, $config, $eventManager);
            });
        } catch (Exception $e) {
            throw new App\Exception\BootstrapException($e->getMessage());
        }
    },
    Doctrine\ORM\EntityManagerInterface::class => DI\Get(App\Doctrine\DecoratedEntityManager::class),

    // Redis cache
    Redis::class => function (Settings $settings) {
        $redis_host = $settings->isDocker() ? 'redis' : 'localhost';

        $redis = new Redis();
        $redis->connect($redis_host, 6379, 15);
        $redis->select(1);

        return $redis;
    },

    Psr\Cache\CacheItemPoolInterface::class => function (Settings $settings, ContainerInterface $di) {
        return !$settings->isTesting()
            ? new Cache\Adapter\Redis\RedisCachePool($di->get(Redis::class))
            : new Cache\Adapter\PHPArray\ArrayCachePool;
    },
    Psr\SimpleCache\CacheInterface::class => DI\get(Psr\Cache\CacheItemPoolInterface::class),

    // Doctrine cache
    Doctrine\Common\Cache\Cache::class => function (
        Settings $settings,
        Psr\Cache\CacheItemPoolInterface $cachePool
    ) {
        if ($settings->isCli()) {
            $cachePool = new Cache\Adapter\PHPArray\ArrayCachePool();
        }

        $cachePool = new Cache\Prefixed\PrefixedCachePool($cachePool, 'doctrine|');

        return new Cache\Bridge\Doctrine\DoctrineCacheBridge($cachePool);
    },

    // Session save handler middleware
    Mezzio\Session\SessionPersistenceInterface::class => function (
        Settings $settings,
        Psr\Cache\CacheItemPoolInterface $cachePool
    ) {
        if ($settings->isCli()) {
            $cachePool = new Cache\Adapter\PHPArray\ArrayCachePool();
        }

        $cachePool = new Cache\Prefixed\PrefixedCachePool($cachePool, 'session|');

        return new Mezzio\Session\Cache\CacheSessionPersistence(
            $cachePool,
            'app_session',
            '/',
            'nocache',
            43200,
            time()
        );
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
    Monolog\Logger::class => function (Settings $settings) {
        $logger = new Monolog\Logger($settings[Settings::APP_NAME] ?? 'app');

        $loggingLevel = null;
        if (!empty($_ENV['LOG_LEVEL'])) {
            $allowedLogLevels = [
                Psr\Log\LogLevel::DEBUG,
                Psr\Log\LogLevel::INFO,
                Psr\Log\LogLevel::NOTICE,
                Psr\Log\LogLevel::WARNING,
                Psr\Log\LogLevel::ERROR,
                Psr\Log\LogLevel::CRITICAL,
                Psr\Log\LogLevel::ALERT,
                Psr\Log\LogLevel::EMERGENCY,
            ];

            $loggingLevel = strtolower($_ENV['LOG_LEVEL']);
            if (!in_array($loggingLevel, $allowedLogLevels, true)) {
                $loggingLevel = null;
            }
        }

        $loggingLevel ??= $settings->isProduction() ? Psr\Log\LogLevel::NOTICE : Psr\Log\LogLevel::DEBUG;

        if ($settings->isDocker() || $settings->isCli()) {
            $log_stderr = new Monolog\Handler\StreamHandler('php://stderr', $loggingLevel, true);
            $logger->pushHandler($log_stderr);
        }

        $log_file = new Monolog\Handler\StreamHandler(
            $settings[Settings::TEMP_DIR] . '/app.log',
            $loggingLevel,
            true
        );
        $logger->pushHandler($log_file);

        return $logger;
    },
    Psr\Log\LoggerInterface::class => DI\get(Monolog\Logger::class),

    // Doctrine annotations reader
    Doctrine\Common\Annotations\Reader::class => function (
        Doctrine\Common\Cache\Cache $doctrine_cache,
        Settings $settings
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
        Doctrine\ORM\EntityManagerInterface $em
    ) {
        $meta_factory = new Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory(
            new Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader($annotation_reader)
        );

        $normalizers = [
            new Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer(),
            new App\Normalizer\DoctrineEntityNormalizer($em, $annotation_reader, $meta_factory),
            new Symfony\Component\Serializer\Normalizer\ObjectNormalizer($meta_factory),
        ];
        $encoders = [
            new Symfony\Component\Serializer\Encoder\JsonEncoder,
        ];

        return new Symfony\Component\Serializer\Serializer($normalizers, $encoders);
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

    Symfony\Component\Messenger\MessageBus::class => function (
        App\MessageQueue\QueueManager $queueManager,
        App\LockFactory $lockFactory,
        Monolog\Logger $logger,
        ContainerInterface $di,
        App\Plugins $plugins
    ) {
        // Configure message sending middleware
        $sendMessageMiddleware = new Symfony\Component\Messenger\Middleware\SendMessageMiddleware($queueManager);
        $sendMessageMiddleware->setLogger($logger);

        // Configure message handling middleware
        $handlers = [];
        $receivers = require __DIR__ . '/messagequeue.php';

        // Register plugin-provided message queue receivers
        $receivers = $plugins->registerMessageQueueReceivers($receivers);

        foreach ($receivers as $messageClass => $handlerClass) {
            $handlers[$messageClass][] = function ($message) use ($handlerClass, $di) {
                $obj = $di->get($handlerClass);
                return $obj($message);
            };
        }

        $handlersLocator = new Symfony\Component\Messenger\Handler\HandlersLocator($handlers);

        $handleMessageMiddleware = new Symfony\Component\Messenger\Middleware\HandleMessageMiddleware(
            $handlersLocator,
            true
        );
        $handleMessageMiddleware->setLogger($logger);

        // Add unique protection middleware
        $uniqueMiddleware = new App\MessageQueue\HandleUniqueMiddleware($lockFactory);

        // Compile finished message bus.
        return new Symfony\Component\Messenger\MessageBus([
            $sendMessageMiddleware,
            $uniqueMiddleware,
            $handleMessageMiddleware,
        ]);
    },

    // Supervisor manager
    Supervisor\Supervisor::class => function (Settings $settings) {
        $client = new fXmlRpc\Client(
            'http://' . ($settings->isDocker() ? 'stations' : '127.0.0.1') . ':9001/RPC2',
            new fXmlRpc\Transport\PsrTransport(
                new Http\Factory\Guzzle\RequestFactory,
                new GuzzleHttp\Client
            )
        );

        $supervisor = new Supervisor\Supervisor($client);

        if (!$supervisor->isConnected()) {
            throw new \App\Exception(sprintf('Could not connect to supervisord.'));
        }

        return $supervisor;
    },

    // NowPlaying Adapter factory
    NowPlaying\Adapter\AdapterFactory::class => function (
        GuzzleHttp\Client $httpClient,
        Psr\Log\LoggerInterface $logger
    ) {
        return new NowPlaying\Adapter\AdapterFactory(
            new Http\Factory\Guzzle\UriFactory,
            new Http\Factory\Guzzle\RequestFactory,
            $httpClient,
            $logger
        );
    },

    App\Media\MetadataManagerInterface::class => DI\get(App\Media\GetId3\GetId3MetadataManager::class),

    // Asset Management
    App\Assets::class => function (App\Config $config, Settings $settings) {
        $libraries = $config->get('assets');

        $versioned_files = [];
        $assets_file = $settings[Settings::BASE_DIR] . '/web/static/assets.json';
        if (file_exists($assets_file)) {
            $versioned_files = json_decode(file_get_contents($assets_file), true, 512, JSON_THROW_ON_ERROR);
        }

        $vueComponents = [];
        $assets_file = $settings[Settings::BASE_DIR] . '/web/static/webpack.json';
        if (file_exists($assets_file)) {
            $vueComponents = json_decode(file_get_contents($assets_file), true, 512, JSON_THROW_ON_ERROR);
        }

        return new App\Assets($libraries, $versioned_files, $vueComponents);
    },

    // Synchronized (Cron) Tasks
    App\Sync\TaskLocator::class => function (ContainerInterface $di) {
        return new App\Sync\TaskLocator($di, [
            App\Event\GetSyncTasks::SYNC_NOWPLAYING => [
                App\Sync\Task\BuildQueue::class,
                App\Sync\Task\NowPlaying::class,
                App\Sync\Task\ReactivateStreamer::class,
            ],
            App\Event\GetSyncTasks::SYNC_SHORT => [
                App\Sync\Task\RadioRequests::class,
                App\Sync\Task\Backup::class,
                App\Sync\Task\RelayCleanup::class,
            ],
            App\Event\GetSyncTasks::SYNC_MEDIUM => [
                App\Sync\Task\Media::class,
                App\Sync\Task\FolderPlaylists::class,
                App\Sync\Task\CheckForUpdates::class,
            ],
            App\Event\GetSyncTasks::SYNC_LONG => [
                App\Sync\Task\Analytics::class,
                App\Sync\Task\RadioAutomation::class,
                App\Sync\Task\HistoryCleanup::class,
                App\Sync\Task\StorageCleanupTask::class,
                App\Sync\Task\RotateLogs::class,
                App\Sync\Task\UpdateGeoLiteDatabase::class,
            ],
        ]);
    },

    // Web Hooks
    App\Webhook\ConnectorLocator::class => function (
        ContainerInterface $di,
        App\Config $config
    ) {
        $webhooks = $config->get('webhooks');
        $services = [];
        foreach ($webhooks['webhooks'] as $webhook_key => $webhook_info) {
            $services[$webhook_key] = $webhook_info['class'];
        }

        return new App\Webhook\ConnectorLocator($di, $services);
    },
];
