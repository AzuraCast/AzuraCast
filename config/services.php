<?php

/**
 * PHP-DI Services
 */

declare(strict_types=1);

use App\Environment;
use App\Event;
use Psr\Container\ContainerInterface;

return [

    // Slim interface
    Slim\Interfaces\RouteCollectorInterface::class => static fn(Slim\App $app) => $app->getRouteCollector(),

    Slim\Interfaces\RouteParserInterface::class => static fn(
        Slim\Interfaces\RouteCollectorInterface $routeCollector
    ) => $routeCollector->getRouteParser(),

    // URL Router helper
    App\Http\RouterInterface::class => DI\Get(App\Http\Router::class),

    // Error handler
    Slim\Interfaces\ErrorHandlerInterface::class => DI\Get(App\Http\ErrorHandler::class),

    // HTTP client
    App\Service\GuzzleFactory::class => static function (Psr\Log\LoggerInterface $logger) {
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

        return new App\Service\GuzzleFactory(
            [
                'handler' => $stack,
                GuzzleHttp\RequestOptions::HTTP_ERRORS => false,
                GuzzleHttp\RequestOptions::TIMEOUT => 3.0,
            ]
        );
    },

    GuzzleHttp\Client::class => static fn(App\Service\GuzzleFactory $guzzleFactory) => $guzzleFactory->buildClient(),

    // DBAL
    Doctrine\DBAL\Connection::class => static fn(Doctrine\ORM\EntityManagerInterface $em) => $em->getConnection(),

    // Doctrine Entity Manager
    App\Doctrine\DecoratedEntityManager::class => static function (
        Psr\Cache\CacheItemPoolInterface $psr6Cache,
        Environment $environment,
        App\Doctrine\Event\StationRequiresRestart $eventRequiresRestart,
        App\Doctrine\Event\AuditLog $eventAuditLog,
        App\Doctrine\Event\SetExplicitChangeTracking $eventChangeTracking,
        Psr\EventDispatcher\EventDispatcherInterface $dispatcher
    ) {
        if ($environment->isCli()) {
            $psr6Cache = new Symfony\Component\Cache\Adapter\ArrayAdapter();
        } else {
            $psr6Cache = new Symfony\Component\Cache\Adapter\ProxyAdapter($psr6Cache, 'doctrine.');
        }

        $dbSettings = $environment->getDatabaseSettings();
        if (isset($dbSettings['unix_socket'])) {
            unset($dbSettings['host'], $dbSettings['port']);
        }

        $connectionOptions = array_merge(
            $dbSettings,
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
                    // PDO::MYSQL_ATTR_LOCAL_INFILE = 1001
                    1001 => true,
                ],
                'platform' => new Doctrine\DBAL\Platforms\MariaDb1027Platform(),
            ]
        );

        $mappingClassesPaths = [$environment->getBaseDirectory() . '/src/Entity'];

        $buildDoctrineMappingPathsEvent = new Event\BuildDoctrineMappingPaths(
            $mappingClassesPaths,
            $environment->getBaseDirectory()
        );
        $dispatcher->dispatch($buildDoctrineMappingPathsEvent);

        $mappingClassesPaths = $buildDoctrineMappingPathsEvent->getMappingClassesPaths();

        // Fetch and store entity manager.
        $config = Doctrine\ORM\ORMSetup::createAttributeMetadataConfiguration(
            $mappingClassesPaths,
            !$environment->isProduction(),
            $environment->getTempDirectory() . '/proxies',
            $psr6Cache
        );

        $config->setAutoGenerateProxyClasses(
            Doctrine\Common\Proxy\AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS_OR_CHANGED
        );

        // Debug mode:
        // $config->setSQLLogger(new Doctrine\DBAL\Logging\EchoSQLLogger);

        $config->addCustomNumericFunction('RAND', DoctrineExtensions\Query\Mysql\Rand::class);
        $config->addCustomStringFunction('FIELD', DoctrineExtensions\Query\Mysql\Field::class);

        if (!Doctrine\DBAL\Types\Type::hasType('carbon_immutable')) {
            Doctrine\DBAL\Types\Type::addType('carbon_immutable', Carbon\Doctrine\CarbonImmutableType::class);
        }

        $eventManager = new Doctrine\Common\EventManager();
        $eventManager->addEventSubscriber($eventRequiresRestart);
        $eventManager->addEventSubscriber($eventAuditLog);
        $eventManager->addEventSubscriber($eventChangeTracking);

        return new App\Doctrine\DecoratedEntityManager(
            fn() => Doctrine\ORM\EntityManager::create($connectionOptions, $config, $eventManager)
        );
    },

    App\Doctrine\ReloadableEntityManagerInterface::class => DI\Get(App\Doctrine\DecoratedEntityManager::class),
    Doctrine\ORM\EntityManagerInterface::class => DI\Get(App\Doctrine\DecoratedEntityManager::class),

    Symfony\Contracts\Cache\CacheInterface::class => static function (
        Environment $environment,
        Psr\Log\LoggerInterface $logger,
        App\Service\RedisFactory $redisFactory
    ) {
        if ($environment->isTesting()) {
            $cacheInterface = new Symfony\Component\Cache\Adapter\ArrayAdapter();
        } elseif ($redisFactory->isSupported()) {
            $cacheInterface = new Symfony\Component\Cache\Adapter\RedisAdapter(
                $redisFactory->createInstance(),
                marshaller: new Symfony\Component\Cache\Marshaller\DefaultMarshaller(
                    $environment->isProduction() ? null : false
                )
            );
        } else {
            $tempDir = $environment->getTempDirectory() . DIRECTORY_SEPARATOR . 'cache';
            $cacheInterface = new Symfony\Component\Cache\Adapter\FilesystemAdapter(
                '',
                0,
                $tempDir
            );
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

    Psr\SimpleCache\CacheInterface::class => static fn(
        Psr\Cache\CacheItemPoolInterface $cache
    ) => new Symfony\Component\Cache\Psr16Cache($cache),

    // Symfony Lock adapter
    Symfony\Component\Lock\PersistingStoreInterface::class => static fn(
        Environment $environment,
        App\Service\RedisFactory $redisFactory
    ) => ($redisFactory->isSupported())
        ? new Symfony\Component\Lock\Store\RedisStore($redisFactory->createInstance())
        : new Symfony\Component\Lock\Store\FlockStore($environment->getTempDirectory()),

    // Console
    App\Console\Application::class => static function (
        DI\Container $di,
        App\CallableEventDispatcherInterface $dispatcher,
        App\Version $version,
        Environment $environment
    ) {
        $console = new App\Console\Application(
            $environment->getAppName() . ' Command Line Tools ('
            . $environment->getAppEnvironmentEnum()->getName() . ')',
            $version->getVersion()
        );
        $console->setDispatcher($dispatcher);

        // Trigger an event for the core app and all plugins to build their CLI commands.
        $event = new Event\BuildConsoleCommands($console, $di);
        $dispatcher->dispatch($event);

        $commandLoader = new Symfony\Component\Console\CommandLoader\ContainerCommandLoader(
            $di,
            $event->getAliases()
        );
        $console->setCommandLoader($commandLoader);

        return $console;
    },

    // Event Dispatcher
    App\CallableEventDispatcherInterface::class => static function (
        DI\Container $di,
        App\Plugins $plugins
    ) {
        $dispatcher = new App\CallableEventDispatcher();
        $dispatcher->setContainer($di);

        // Register application default events.
        if (file_exists(__DIR__ . '/events.php')) {
            call_user_func(include(__DIR__ . '/events.php'), $dispatcher);
        }

        // Register plugin-provided events.
        $plugins->registerEvents($dispatcher);

        return $dispatcher;
    },

    Psr\EventDispatcher\EventDispatcherInterface::class => DI\get(
        App\CallableEventDispatcherInterface::class
    ),

    // Monolog Logger
    Monolog\Logger::class => static function (Environment $environment) {
        $logger = new Monolog\Logger($environment->getAppName());
        $loggingLevel = $environment->getLogLevel();

        if ($environment->isCli() || $environment->isDocker()) {
            $logStderr = new Monolog\Handler\StreamHandler('php://stderr', $loggingLevel, true);
            $logger->pushHandler($logStderr);
        }

        $logFile = new Monolog\Handler\RotatingFileHandler(
            $environment->getTempDirectory() . '/app.log',
            5,
            $loggingLevel,
            true
        );
        $logger->pushHandler($logFile);

        return $logger;
    },

    Psr\Log\LoggerInterface::class => DI\get(Monolog\Logger::class),

    // Symfony Serializer
    Symfony\Component\Serializer\Serializer::class => static function (
        App\Doctrine\ReloadableEntityManagerInterface $em
    ) {
        $classMetaFactory = new Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory(
            new Symfony\Component\Serializer\Mapping\Loader\AttributeLoader()
        );

        $normalizers = [
            new Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer(),
            new Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer(),
            new App\Normalizer\DoctrineEntityNormalizer(
                $em,
                classMetadataFactory: $classMetaFactory
            ),
            new Symfony\Component\Serializer\Normalizer\ObjectNormalizer(
                classMetadataFactory: $classMetaFactory
            ),
        ];
        $encoders = [
            new Symfony\Component\Serializer\Encoder\JsonEncoder(),
        ];

        return new Symfony\Component\Serializer\Serializer($normalizers, $encoders);
    },

    // Symfony Validator
    Symfony\Component\Validator\Validator\ValidatorInterface::class => static function (
        Symfony\Component\Validator\ContainerConstraintValidatorFactory $constraintValidatorFactory
    ) {
        $builder = new Symfony\Component\Validator\ValidatorBuilder();
        $builder->setConstraintValidatorFactory($constraintValidatorFactory);
        $builder->enableAttributeMapping();

        return $builder->getValidator();
    },

    App\MessageQueue\QueueManagerInterface::class => static function (
        App\Service\RedisFactory $redisFactory,
        Environment $environment
    ) {
        return ($redisFactory->isSupported())
            ? new App\MessageQueue\QueueManager($environment)
            : new App\MessageQueue\TestQueueManager();
    },

    Symfony\Component\Messenger\MessageBus::class => static function (
        App\MessageQueue\QueueManager $queueManager,
        App\Lock\LockFactory $lockFactory,
        Monolog\Logger $logger,
        ContainerInterface $di,
        App\Plugins $plugins,
        App\Service\RedisFactory $redisFactory,
        Environment $environment
    ) {
        $loggingLevel = $environment->getLogLevel();
        $busLogger = new Psr\Log\NullLogger();

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
        $handleMessageMiddleware->setLogger($busLogger);

        // On testing, messages are handled directly when called
        if (!$redisFactory->isSupported()) {
            return new Symfony\Component\Messenger\MessageBus(
                [
                    $handleMessageMiddleware,
                ]
            );
        }

        // Add unique protection middleware
        $uniqueMiddleware = new App\MessageQueue\HandleUniqueMiddleware($lockFactory);

        // Configure message sending middleware
        $sendMessageMiddleware = new Symfony\Component\Messenger\Middleware\SendMessageMiddleware($queueManager);
        $sendMessageMiddleware->setLogger($busLogger);

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
        Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher,
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

    Symfony\Component\Mailer\Mailer::class => static fn(
        Symfony\Component\Mailer\Transport\TransportInterface $transport,
        Symfony\Component\Messenger\MessageBus $messageBus,
        Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher
    ) => new Symfony\Component\Mailer\Mailer($transport, $messageBus, $eventDispatcher),

    Symfony\Component\Mailer\MailerInterface::class => DI\get(
        Symfony\Component\Mailer\Mailer::class
    ),

    // Supervisor manager
    Supervisor\SupervisorInterface::class => static fn(
        Environment $environment,
        Psr\Log\LoggerInterface $logger
    ) => new Supervisor\Supervisor(
        new fXmlRpc\Client(
            'http://localhost/RPC2',
            new fXmlRpc\Transport\PsrTransport(
                new GuzzleHttp\Psr7\HttpFactory(),
                new GuzzleHttp\Client([
                    'curl' => [
                        \CURLOPT_UNIX_SOCKET_PATH => '/var/run/supervisor.sock',
                    ],
                ])
            )
        ),
        $logger
    ),

    // NowPlaying Adapter factory
    NowPlaying\AdapterFactory::class => static function (
        GuzzleHttp\Client $httpClient,
        Psr\Log\LoggerInterface $logger
    ) {
        $httpFactory = new GuzzleHttp\Psr7\HttpFactory();

        return new NowPlaying\AdapterFactory(
            $httpFactory,
            $httpFactory,
            $httpClient,
            $logger
        );
    },
];
