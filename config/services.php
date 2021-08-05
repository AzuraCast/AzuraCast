<?php
/**
 * PHP-DI Services
 */

use App\Environment;
use App\Event;
use Psr\Container\ContainerInterface;

return [

    // Slim interface
    Slim\Interfaces\RouteCollectorInterface::class => static function (Slim\App $app) {
        return $app->getRouteCollector();
    },

    Slim\Interfaces\RouteParserInterface::class => static function (
        Slim\Interfaces\RouteCollectorInterface $routeCollector
    ) {
        return $routeCollector->getRouteParser();
    },

    // URL Router helper
    App\Http\RouterInterface::class => DI\Get(App\Http\Router::class),

    // Error handler
    Slim\Interfaces\ErrorHandlerInterface::class => DI\Get(App\Http\ErrorHandler::class),

    // HTTP client
    GuzzleHttp\Client::class => static function (Psr\Log\LoggerInterface $logger) {
        $stack = GuzzleHttp\HandlerStack::create();

        $stack->unshift(
            function (callable $handler) {
                return static function (Psr\Http\Message\RequestInterface $request, array $options) use ($handler) {
                    $options[GuzzleHttp\RequestOptions::VERIFY] = Composer\CaBundle\CaBundle::getSystemCaRootBundlePath(
                    );
                    return $handler($request, $options);
                };
            },
            'ssl_verify'
        );

        $stack->push(
            GuzzleHttp\Middleware::log(
                $logger,
                new GuzzleHttp\MessageFormatter('HTTP client {method} call to {uri} produced response {code}'),
                Psr\Log\LogLevel::DEBUG
            )
        );

        return new GuzzleHttp\Client(
            [
                'handler' => $stack,
                GuzzleHttp\RequestOptions::HTTP_ERRORS => false,
                GuzzleHttp\RequestOptions::TIMEOUT => 3.0,
            ]
        );
    },

    // DBAL
    Doctrine\DBAL\Connection::class => static function (Doctrine\ORM\EntityManagerInterface $em) {
        return $em->getConnection();
    },

    // Doctrine Entity Manager
    App\Doctrine\DecoratedEntityManager::class => static function (
        Doctrine\Common\Cache\Cache $doctrineCache,
        Environment $environment,
        App\Doctrine\Event\StationRequiresRestart $eventRequiresRestart,
        App\Doctrine\Event\AuditLog $eventAuditLog,
        App\Doctrine\Event\SetExplicitChangeTracking $eventChangeTracking,
        Psr\EventDispatcher\EventDispatcherInterface $dispatcher
    ) {
        $connectionOptions = array_merge(
            $environment->getDatabaseSettings(),
            [
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
            ]
        );

        try {
            // Fetch and store entity manager.
            $config = Doctrine\ORM\Tools\Setup::createConfiguration(
                Doctrine\Common\Proxy\AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS,
                $environment->getTempDirectory() . '/proxies',
                $doctrineCache
            );

            $mappingClassesPaths = [$environment->getBaseDirectory() . '/src/Entity'];

            $buildDoctrineMappingPathsEvent = new Event\BuildDoctrineMappingPaths(
                $mappingClassesPaths,
                $environment->getBaseDirectory()
            );
            $dispatcher->dispatch($buildDoctrineMappingPathsEvent);

            $mappingClassesPaths = $buildDoctrineMappingPathsEvent->getMappingClassesPaths();

            $attributeDriver = new Doctrine\ORM\Mapping\Driver\AttributeDriver(
                $mappingClassesPaths
            );
            $config->setMetadataDriverImpl($attributeDriver);

            // Debug mode:
            // $config->setSQLLogger(new Doctrine\DBAL\Logging\EchoSQLLogger);

            $config->addCustomNumericFunction('RAND', DoctrineExtensions\Query\Mysql\Rand::class);

            if (!Doctrine\DBAL\Types\Type::hasType('carbon_immutable')) {
                Doctrine\DBAL\Types\Type::addType('carbon_immutable', Carbon\Doctrine\CarbonImmutableType::class);
            }

            $eventManager = new Doctrine\Common\EventManager;
            $eventManager->addEventSubscriber($eventRequiresRestart);
            $eventManager->addEventSubscriber($eventAuditLog);
            $eventManager->addEventSubscriber($eventChangeTracking);

            return new App\Doctrine\DecoratedEntityManager(
                function () use (
                    $connectionOptions,
                    $config,
                    $eventManager
                ) {
                    return Doctrine\ORM\EntityManager::create($connectionOptions, $config, $eventManager);
                }
            );
        } catch (Exception $e) {
            throw new App\Exception\BootstrapException($e->getMessage());
        }
    },

    App\Doctrine\ReloadableEntityManagerInterface::class => DI\Get(App\Doctrine\DecoratedEntityManager::class),
    Doctrine\ORM\EntityManagerInterface::class => DI\Get(App\Doctrine\DecoratedEntityManager::class),

    // Redis cache
    Redis::class => static function (Environment $environment) {
        if (!$environment->enableRedis()) {
            throw new App\Exception\BootstrapException('Redis is disabled on this installation.');
        }

        $settings = $environment->getRedisSettings();

        $redis = new Redis();
        $redis->connect($settings['host'], $settings['port'], 15);
        $redis->select($settings['db']);

        return $redis;
    },

    Symfony\Contracts\Cache\CacheInterface::class => static function (
        Environment $environment,
        Psr\Log\LoggerInterface $logger,
        ContainerInterface $di
    ) {
        /** @var Symfony\Contracts\Cache\CacheInterface $cacheInterface */
        if ($environment->isTesting()) {
            $cacheInterface = new Symfony\Component\Cache\Adapter\ArrayAdapter();
        } elseif (!$environment->enableRedis()) {
            $tempDir = $environment->getTempDirectory() . DIRECTORY_SEPARATOR . 'cache';
            $cacheInterface = new Symfony\Component\Cache\Adapter\FilesystemAdapter(
                '',
                0,
                $tempDir
            );
        } else {
            $cacheInterface = new Symfony\Component\Cache\Adapter\RedisAdapter($di->get(Redis::class));
        }

        $cacheInterface->setLogger($logger);
        return $cacheInterface;
    },

    Symfony\Component\Cache\Adapter\AdapterInterface::class => DI\get(
        Symfony\Contracts\Cache\CacheInterface::class
    ),
    Psr\Cache\CacheItemPoolInterface::class => DI\get(
        Symfony\Contracts\Cache\CacheInterface::class
    ),
    Psr\SimpleCache\CacheInterface::class => static function (Psr\Cache\CacheItemPoolInterface $cache) {
        return new Symfony\Component\Cache\Psr16Cache($cache);
    },

    // Doctrine cache
    Doctrine\Common\Cache\Cache::class => static function (
        Environment $environment,
        Psr\Cache\CacheItemPoolInterface $psr6Cache
    ) {
        if ($environment->isCli()) {
            $psr6Cache = new Symfony\Component\Cache\Adapter\ArrayAdapter();
        }

        $proxyCache = new Symfony\Component\Cache\Adapter\ProxyAdapter($psr6Cache, 'doctrine.');
        return Doctrine\Common\Cache\Psr6\DoctrineProvider::wrap($proxyCache);
    },

    // Symfony Lock adapter
    Symfony\Component\Lock\PersistingStoreInterface::class => static function (
        ContainerInterface $di,
        Environment $environment
    ) {
        if ($environment->enableRedis()) {
            $redis = $di->get(Redis::class);
            $store = new Symfony\Component\Lock\Store\RedisStore($redis);
        } else {
            $store = new Symfony\Component\Lock\Store\FlockStore($environment->getTempDirectory());
        }

        return $store;
    },

    // Session save handler middleware
    Mezzio\Session\SessionPersistenceInterface::class => static function (
        Environment $environment,
        Psr\Cache\CacheItemPoolInterface $cachePool
    ) {
        if ($environment->isCli()) {
            $cachePool = new Symfony\Component\Cache\Adapter\ArrayAdapter();
        }

        $cachePool = new Symfony\Component\Cache\Adapter\ProxyAdapter($cachePool, 'session.');

        return new Mezzio\Session\Cache\CacheSessionPersistence(
            $cachePool,
            'app_session',
            '/',
            'nocache',
            43200,
            time(),
            true
        );
    },

    // Console
    App\Console\Application::class => static function (
        DI\Container $di,
        Azura\SlimCallableEventDispatcher\CallableEventDispatcherInterface $dispatcher,
        App\Version $version,
        Environment $environment
    ) {
        $console = new App\Console\Application(
            $environment->getAppName() . ' Command Line Tools (' . $environment->getAppEnvironment() . ')',
            $version->getVersion(),
            $di
        );
        $console->setDispatcher($dispatcher);

        // Trigger an event for the core app and all plugins to build their CLI commands.
        $event = new App\Event\BuildConsoleCommands($console);
        $dispatcher->dispatch($event);

        return $console;
    },

    // Event Dispatcher
    Azura\SlimCallableEventDispatcher\CallableEventDispatcherInterface::class => static function (
        Slim\App $app,
        App\Plugins $plugins
    ) {
        $dispatcher = new Azura\SlimCallableEventDispatcher\SlimCallableEventDispatcher($app->getCallableResolver());

        // Register application default events.
        if (file_exists(__DIR__ . '/events.php')) {
            call_user_func(include(__DIR__ . '/events.php'), $dispatcher);
        }

        // Register plugin-provided events.
        $plugins->registerEvents($dispatcher);

        return $dispatcher;
    },
    Psr\EventDispatcher\EventDispatcherInterface::class => DI\get(
        Azura\SlimCallableEventDispatcher\CallableEventDispatcherInterface::class
    ),

    // Monolog Logger
    Monolog\Logger::class => static function (Environment $environment) {
        $logger = new Monolog\Logger($environment->getAppName());
        $loggingLevel = $environment->getLogLevel();

        if ($environment->isDocker() || $environment->isCli()) {
            $log_stderr = new Monolog\Handler\StreamHandler('php://stderr', $loggingLevel, true);
            $logger->pushHandler($log_stderr);
        }

        $log_file = new Monolog\Handler\RotatingFileHandler(
            $environment->getTempDirectory() . '/app.log',
            5,
            $loggingLevel,
            true
        );
        $logger->pushHandler($log_file);

        return $logger;
    },
    Psr\Log\LoggerInterface::class => DI\get(Monolog\Logger::class),

    // Doctrine annotations reader
    Doctrine\Common\Annotations\Reader::class => static function (
        Psr\Cache\CacheItemPoolInterface $psr6Cache,
        Environment $settings
    ) {
        $proxyCache = new Symfony\Component\Cache\Adapter\ProxyAdapter($psr6Cache, 'annotations.');

        return new Doctrine\Common\Annotations\PsrCachedReader(
            new Doctrine\Common\Annotations\AnnotationReader,
            $proxyCache,
            !$settings->isProduction()
        );
    },

    // Symfony Serializer
    Symfony\Component\Serializer\Serializer::class => static function (
        Doctrine\Common\Annotations\Reader $reader,
        Doctrine\ORM\EntityManagerInterface $em
    ) {
        $classMetaFactory = new Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory(
            new Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader($reader)
        );

        $normalizers = [
            new Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer(),
            new App\Normalizer\DoctrineEntityNormalizer($em, $classMetaFactory),
            new Symfony\Component\Serializer\Normalizer\ObjectNormalizer($classMetaFactory),
        ];
        $encoders = [
            new Symfony\Component\Serializer\Encoder\JsonEncoder,
        ];

        return new Symfony\Component\Serializer\Serializer($normalizers, $encoders);
    },

    // Symfony Validator
    Symfony\Component\Validator\Validator\ValidatorInterface::class => static function (
        Doctrine\Common\Annotations\Reader $reader,
        Symfony\Component\Validator\ContainerConstraintValidatorFactory $constraintValidatorFactory
    ) {
        $builder = new Symfony\Component\Validator\ValidatorBuilder();
        $builder->setConstraintValidatorFactory($constraintValidatorFactory);
        $builder->enableAnnotationMapping($reader);
        return $builder->getValidator();
    },

    Pheanstalk\Pheanstalk::class => static function () {
        return Pheanstalk\Pheanstalk::create('127.0.0.1', 11300);
    },

    App\MessageQueue\QueueManagerInterface::class => static function (
        Environment $environment,
        ContainerInterface $di
    ) {
        if ($environment->isTesting()) {
            return new App\MessageQueue\TestQueueManager();
        }

        $pheanstalk = $di->get(Pheanstalk\Pheanstalk::class);
        return new App\MessageQueue\QueueManager($pheanstalk);
    },

    Symfony\Component\Messenger\MessageBus::class => static function (
        App\MessageQueue\QueueManager $queueManager,
        App\LockFactory $lockFactory,
        Monolog\Logger $logger,
        ContainerInterface $di,
        App\Plugins $plugins,
        Environment $environment
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
            $handlers[$messageClass][] = static function ($message) use ($handlerClass, $di) {
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

        // On testing, messages are handled directly when called
        if ($environment->isTesting()) {
            return new Symfony\Component\Messenger\MessageBus(
                [
                    $handleMessageMiddleware,
                ]
            );
        }

        // Compile finished message bus.
        return new Symfony\Component\Messenger\MessageBus(
            [
                $sendMessageMiddleware,
                $uniqueMiddleware,
                $handleMessageMiddleware,
            ]
        );
    },

    Symfony\Component\Messenger\MessageBusInterface::class => DI\get(
        Symfony\Component\Messenger\MessageBus::class
    ),

    // Mail functionality
    Symfony\Component\Mailer\Transport\TransportInterface::class => static function (
        App\Entity\Repository\SettingsRepository $settingsRepo,
        Azura\SlimCallableEventDispatcher\CallableEventDispatcherInterface $eventDispatcher,
        Monolog\Logger $logger
    ) {
        $settings = $settingsRepo->readSettings();

        if ($settings->getMailEnabled()) {
            $requiredSettings = [
                'mailSenderEmail' => $settings->getMailSenderEmail(),
                'mailSmtpHost' => $settings->getMailSmtpHost(),
                'mailSmtpPort' => $settings->getMailSmtpPort(),
            ];

            $hasAllSettings = true;
            foreach ($requiredSettings as $setting) {
                if (empty($setting)) {
                    $hasAllSettings = false;
                    break;
                }
            }

            if ($hasAllSettings) {
                $transport = new Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                    $settings->getMailSmtpHost(),
                    $settings->getMailSmtpPort(),
                    $settings->getMailSmtpSecure(),
                    $eventDispatcher,
                    $logger
                );

                if (!empty($settings->getMailSmtpUsername())) {
                    $transport->setUsername($settings->getMailSmtpUsername());
                    $transport->setPassword($settings->getMailSmtpPassword());
                }

                return $transport;
            }
        }

        return new Symfony\Component\Mailer\Transport\NullTransport(
            $eventDispatcher,
            $logger
        );
    },

    Symfony\Component\Mailer\Mailer::class => static function (
        Symfony\Component\Mailer\Transport\TransportInterface $transport,
        Symfony\Component\Messenger\MessageBus $messageBus,
        Azura\SlimCallableEventDispatcher\CallableEventDispatcherInterface $eventDispatcher
    ) {
        return new Symfony\Component\Mailer\Mailer(
            $transport,
            $messageBus,
            $eventDispatcher
        );
    },

    Symfony\Component\Mailer\MailerInterface::class => DI\get(
        Symfony\Component\Mailer\Mailer::class
    ),

    // Supervisor manager
    Supervisor\Supervisor::class => static function (Environment $settings, Psr\Log\LoggerInterface $logger) {
        /** @noinspection HttpUrlsUsage */
        $client = new fXmlRpc\Client(
            'http://' . ($settings->isDocker() ? 'stations' : '127.0.0.1') . ':9001/RPC2',
            new fXmlRpc\Transport\PsrTransport(
                new Http\Factory\Guzzle\RequestFactory,
                new GuzzleHttp\Client
            )
        );

        return new Supervisor\Supervisor($client, $logger);
    },

    // Image Manager
    Intervention\Image\ImageManager::class => static function () {
        return new Intervention\Image\ImageManager(
            [
                'driver' => 'gd',
            ]
        );
    },

    // NowPlaying Adapter factory
    NowPlaying\AdapterFactory::class => static function (
        GuzzleHttp\Client $httpClient,
        Psr\Log\LoggerInterface $logger
    ) {
        return new NowPlaying\AdapterFactory(
            new Http\Factory\Guzzle\UriFactory,
            new Http\Factory\Guzzle\RequestFactory,
            $httpClient,
            $logger
        );
    },

];
