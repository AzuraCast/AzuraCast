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
    Doctrine\DBAL\Connection::class => static function (
        Environment $environment,
        Psr\Cache\CacheItemPoolInterface $psr6Cache,
    ) {
        $dbSettings = $environment->getDatabaseSettings();
        if (isset($dbSettings['unix_socket'])) {
            unset($dbSettings['host'], $dbSettings['port']);
        }

        $connectionOptions = [
            ...$dbSettings,
            'driver' => 'pdo_mysql',
            'charset' => 'utf8mb4',
            'defaultTableOptions' => [
                'charset' => 'utf8mb4',
                'collate' => 'utf8mb4_general_ci',
            ],
            'driverOptions' => [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_general_ci; '
                    . 'SET sql_mode=(SELECT REPLACE(@@sql_mode, "ONLY_FULL_GROUP_BY", ""))',
                PDO::MYSQL_ATTR_LOCAL_INFILE => true,
            ],
        ];

        // Specify MariaDB version for local Docker installs. Let non-local ones auto-detect via Doctrine.
        if (isset($connectionOptions['unix_socket']) || $environment->isTesting()) {
            $connectionOptions['serverVersion'] = '11.8.2-MariaDB-1';
        }

        $config = new Doctrine\DBAL\Configuration();
        $config->setResultCache($psr6Cache);

        // Add middleware that forces a custom platform, for high-precision DATETIMEs.
        $config->setMiddlewares([
            new class implements Doctrine\DBAL\Driver\Middleware {
                public function wrap(Doctrine\DBAL\Driver $driver): Doctrine\DBAL\Driver
                {
                    return new class ($driver) extends Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware {
                        public function getDatabasePlatform(
                            Doctrine\DBAL\ServerVersionProvider $versionProvider
                        ): Doctrine\DBAL\Platforms\AbstractPlatform {
                            return new App\Doctrine\Platform\MariaDbPlatform();
                        }
                    };
                }
            },
        ]);

        /** @phpstan-ignore-next-line */
        return Doctrine\DBAL\DriverManager::getConnection($connectionOptions, $config);
    },

    // Doctrine Entity Manager
    App\Doctrine\DecoratedEntityManager::class => static function (
        Doctrine\DBAL\Connection $connection,
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
            $psr6Cache = App\Cache\CacheNamespace::Doctrine->withNamespace($psr6Cache);
        }

        $mappingClassesPaths = [$environment->getBackendDirectory() . '/src/Entity'];

        $buildDoctrineMappingPathsEvent = new Event\BuildDoctrineMappingPaths(
            $mappingClassesPaths,
            $environment->getBaseDirectory()
        );
        $dispatcher->dispatch($buildDoctrineMappingPathsEvent);

        $mappingClassesPaths = $buildDoctrineMappingPathsEvent->getMappingClassesPaths();

        // Fetch and store entity manager.
        $config = Doctrine\ORM\ORMSetup::createAttributeMetadataConfig(
            $mappingClassesPaths,
            !$environment->isProduction(),
            cache: $psr6Cache
        );

        $config->enableNativeLazyObjects(true);

        // Debug mode:
        // $config->setSQLLogger(new Doctrine\DBAL\Logging\EchoSQLLogger);

        $config->addCustomNumericFunction('RAND', DoctrineExtensions\Query\Mysql\Rand::class);
        $config->addCustomStringFunction('FIELD', DoctrineExtensions\Query\Mysql\Field::class);

        Doctrine\DBAL\Types\Type::overrideType(
            'datetime_immutable',
            App\Doctrine\Types\UtcDateTimeImmutableType::class
        );

        $eventManager = new Doctrine\Common\EventManager();
        $eventManager->addEventSubscriber($eventRequiresRestart);
        $eventManager->addEventSubscriber($eventAuditLog);
        $eventManager->addEventSubscriber($eventChangeTracking);

        return new App\Doctrine\DecoratedEntityManager(
            fn() => new Doctrine\ORM\EntityManager($connection, $config, $eventManager)
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
                $redisFactory->getInstance(),
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
        ? new Symfony\Component\Lock\Store\RedisStore($redisFactory->getInstance())
        : new Symfony\Component\Lock\Store\FlockStore($environment->getTempDirectory()),

    // DB migrator configuration
    Doctrine\Migrations\Configuration\Migration\ConfigurationLoader::class => static function (
        Environment $environment,
        App\CallableEventDispatcherInterface $dispatcher,
    ) {
        $migrationConfigurations = [
            'migrations_paths' => [
                'App\Entity\Migration' => $environment->getBackendDirectory() . '/src/Entity/Migration',
            ],
            'table_storage' => [
                'table_name' => 'app_migrations',
                'version_column_length' => 191,
            ],
            'custom_template' => $environment->getBaseDirectory() . '/util/doctrine_migration.php.tmpl',
        ];

        $buildMigrationConfigurationsEvent = new Event\BuildMigrationConfigurationArray(
            $migrationConfigurations,
            $environment->getBaseDirectory()
        );
        $dispatcher->dispatch($buildMigrationConfigurationsEvent);

        $migrationConfigurations = $buildMigrationConfigurationsEvent->getMigrationConfigurations();

        return new Doctrine\Migrations\Configuration\Migration\ConfigurationArray(
            $migrationConfigurations
        );
    },

    // Console
    App\Console\Application::class => static function (
        DI\Container $di,
        App\CallableEventDispatcherInterface $dispatcher,
        App\Version $version,
        Environment $environment,
        Doctrine\ORM\EntityManagerInterface $em,
        Doctrine\Migrations\Configuration\Migration\ConfigurationLoader $migrateConfig,
        Monolog\Logger $logger,
    ) {
        $console = new App\Console\Application(
            $environment->getAppName() . ' Command Line Tools ('
            . $environment->getAppEnvironmentEnum()->getName() . ')',
            $version->getVersion()
        );
        $console->setDispatcher($dispatcher);

        $logHandler = new Symfony\Bridge\Monolog\Handler\ConsoleHandler();
        $logger->pushHandler($logHandler);
        $dispatcher->addSubscriber($logHandler);

        // Doctrine ORM/DBAL
        Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands(
            $console,
            new Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider($em)
        );

        // Add Doctrine Migrations
        $migrateFactory = Doctrine\Migrations\DependencyFactory::fromEntityManager(
            $migrateConfig,
            new Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager($em),
            $logger
        );
        Doctrine\Migrations\Tools\Console\ConsoleRunner::addCommands($console, $migrateFactory);

        // Trigger an event for the core app and all plugins to build their CLI commands.
        $event = new Event\BuildConsoleCommands($console, $di, $environment);
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
            new App\Normalizer\DateTimeNormalizer(),
            new Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer(),
            new Symfony\Component\Serializer\Normalizer\CustomNormalizer(),
            new Azura\Normalizer\DoctrineEntityNormalizer(
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
        App\Service\RedisFactory $redisFactory
    ) {
        return ($redisFactory->isSupported())
            ? new App\MessageQueue\QueueManager($redisFactory)
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

        /**
         * @var class-string $messageClass
         * @var class-string $handlerClass
         */
        foreach ($receivers as $messageClass => $handlerClass) {
            $handlers[$messageClass][] = static function ($message) use ($handlerClass, $di) {
                /** @var callable $obj */
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

        if ($settings->mail_enabled) {
            $requiredSettings = [
                'mailSenderEmail' => $settings->mail_sender_email,
                'mailSmtpHost' => $settings->mail_smtp_host,
                'mailSmtpPort' => $settings->mail_smtp_port,
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
                    $settings->mail_smtp_host ?? '',
                    $settings->mail_smtp_port,
                    $settings->mail_smtp_secure,
                    $eventDispatcher,
                    $logger
                );

                if (!empty($settings->mail_smtp_username)) {
                    $transport->setUsername($settings->mail_smtp_username);
                }

                if (!empty($settings->mail_smtp_password)) {
                    $transport->setPassword($settings->mail_smtp_password);
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
                        CURLOPT_UNIX_SOCKET_PATH => '/var/run/supervisor.sock',
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
