<?php
/**
 * PHP-DI Services
 */

use App\Settings;
use Doctrine\ORM\EntityManager;
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
            Monolog\Logger::DEBUG
        ));

        return new GuzzleHttp\Client([
            'handler' => $stack,
            GuzzleHttp\RequestOptions::HTTP_ERRORS => false,
            GuzzleHttp\RequestOptions::TIMEOUT => 3.0,
        ]);
    },

    // DBAL
    Doctrine\DBAL\Connection::class => function (Doctrine\ORM\EntityManager $em) {
        return $em->getConnection();
    },
    'db' => DI\Get(Doctrine\DBAL\Connection::class),

    // Doctrine Entity Manager
    Doctrine\ORM\EntityManager::class => function (
        Doctrine\Common\Cache\Cache $doctrine_cache,
        Doctrine\Common\Annotations\Reader $reader,
        App\Settings $settings,
        App\Doctrine\Event\StationRequiresRestart $eventRequiresRestart,
        App\Doctrine\Event\AuditLog $eventAuditLog
    ) {
        $defaults = [
            'cache' => $doctrine_cache,
            'autoGenerateProxies' => !$settings->isProduction(),
            'proxyNamespace' => 'AppProxy',
            'proxyPath' => $settings->getTempDirectory() . '/proxies',
            'modelPath' => $settings->getBaseDirectory() . '/src/Entity',
            'useSimpleAnnotations' => false,
            'conn' => [
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
            ],
        ];

        if (!$settings[App\Settings::IS_DOCKER]) {
            $defaults['conn']['host'] = $_ENV['db_host'] ?? 'localhost';
            $defaults['conn']['port'] = $_ENV['db_port'] ?? '3306';
            $defaults['conn']['dbname'] = $_ENV['db_name'] ?? 'azuracast';
            $defaults['conn']['user'] = $_ENV['db_username'] ?? 'azuracast';
            $defaults['conn']['password'] = $_ENV['db_password'];
        }

        $app_options = $settings[App\Settings::DOCTRINE_OPTIONS] ?? [];
        $options = array_merge($defaults, $app_options);

        try {
            // Fetch and store entity manager.
            $config = new Doctrine\ORM\Configuration;

            if ($options['useSimpleAnnotations']) {
                $metadata_driver = $config->newDefaultAnnotationDriver((array)$options['modelPath'],
                    $options['useSimpleAnnotations']);
            } else {
                $metadata_driver = new Doctrine\ORM\Mapping\Driver\AnnotationDriver(
                    $reader,
                    (array)$options['modelPath']
                );
            }
            $config->setMetadataDriverImpl($metadata_driver);

            $config->setMetadataCacheImpl($options['cache']);
            $config->setQueryCacheImpl($options['cache']);
            $config->setResultCacheImpl($options['cache']);

            $config->setProxyDir($options['proxyPath']);
            $config->setProxyNamespace($options['proxyNamespace']);
            $config->setAutoGenerateProxyClasses(Doctrine\Common\Proxy\AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

            if (isset($options['conn']['debug']) && $options['conn']['debug']) {
                $config->setSQLLogger(new Doctrine\DBAL\Logging\EchoSQLLogger);
            }

            $config->addCustomNumericFunction('RAND', App\Doctrine\Functions\Rand::class);

            $eventManager = new Doctrine\Common\EventManager;
            $eventManager->addEventSubscriber($eventRequiresRestart);
            $eventManager->addEventSubscriber($eventAuditLog);

            return Doctrine\ORM\EntityManager::create($options['conn'], $config, $eventManager);
        } catch (Exception $e) {
            throw new App\Exception\BootstrapException($e->getMessage());
        }
    },
    'em' => DI\Get(Doctrine\ORM\EntityManager::class),

    // Cache
    Psr\Cache\CacheItemPoolInterface::class => function (App\Settings $settings, Psr\Container\ContainerInterface $di) {
        // Never use the Redis cache for CLI commands, as the CLI commands are where
        // the Redis cache gets flushed, so this will lead to a race condition that can't
        // be solved within the application.
        return $settings->enableRedis() && !$settings->isCli()
            ? new Cache\Adapter\Redis\RedisCachePool($di->get(Redis::class))
            : new Cache\Adapter\PHPArray\ArrayCachePool;
    },
    Psr\SimpleCache\CacheInterface::class => DI\get(Psr\Cache\CacheItemPoolInterface::class),

    // Doctrine cache
    Doctrine\Common\Cache\Cache::class => function (Psr\Cache\CacheItemPoolInterface $cachePool) {
        return new Cache\Bridge\Doctrine\DoctrineCacheBridge(new Cache\Prefixed\PrefixedCachePool($cachePool,
            'doctrine|'));
    },

    // Session save handler middleware
    Mezzio\Session\SessionPersistenceInterface::class => function (Cache\Adapter\Redis\RedisCachePool $redisPool) {
        return new Mezzio\Session\Cache\CacheSessionPersistence(
            new Cache\Prefixed\PrefixedCachePool($redisPool, 'session|'),
            'app_session',
            '/',
            'nocache',
            43200,
            time()
        );
    },

    // Redis cache
    Redis::class => function (App\Settings $settings) {
        $redis_host = $settings[App\Settings::IS_DOCKER] ? 'redis' : 'localhost';

        $redis = new Redis();
        $redis->connect($redis_host, 6379, 15);
        $redis->select(1);

        return $redis;
    },

    // View (Plates Templates)
    App\View::class => function (
        Psr\Container\ContainerInterface $di,
        App\Settings $settings,
        App\Http\RouterInterface $router,
        App\EventDispatcher $dispatcher
    ) {
        $view = new App\View($settings[App\Settings::VIEWS_DIR], 'phtml');

        $view->registerFunction('service', function ($service) use ($di) {
            return $di->get($service);
        });

        $view->registerFunction('escapeJs', function ($string) {
            return json_encode($string, JSON_THROW_ON_ERROR, 512);
        });

        $view->addData([
            'settings' => $settings,
            'router' => $router,
            'assets' => $di->get(App\Assets::class),
            'auth' => $di->get(App\Auth::class),
            'acl' => $di->get(App\Acl::class),
            'customization' => $di->get(App\Customization::class),
            'version' => $di->get(App\Version::class),
        ]);

        $view->registerFunction('mailto', function ($address, $link_text = null) {
            $address = substr(chunk_split(bin2hex(" $address"), 2, ';&#x'), 3, -3);
            $link_text = $link_text ?? $address;
            return '<a href="mailto:' . $address . '">' . $link_text . '</a>';
        });
        $view->registerFunction('pluralize', function ($word, $num = 0) {
            if ((int)$num === 1) {
                return $word;
            }
            return Doctrine\Common\Inflector\Inflector::pluralize($word);
        });
        $view->registerFunction('truncate', function ($text, $length = 80) {
            return App\Utilities::truncateText($text, $length);
        });
        $view->registerFunction('truncateUrl', function ($url) {
            return App\Utilities::truncateUrl($url);
        });
        $view->registerFunction('link', function ($url, $external = true, $truncate = true) {
            $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

            $a = ['href="' . $url . '"'];
            if ($external) {
                $a[] = 'target="_blank"';
            }

            $a_body = ($truncate) ? App\Utilities::truncateUrl($url) : $url;
            return '<a ' . implode(' ', $a) . '>' . $a_body . '</a>';
        });

        $dispatcher->dispatch(new App\Event\BuildView($view));

        return $view;
    },

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

    // Asset Management
    App\Assets::class => function (App\Config $config, Settings $settings) {
        $libraries = $config->get('assets');

        $versioned_files = [];
        $assets_file = $settings[Settings::BASE_DIR] . '/web/static/assets.json';
        if (file_exists($assets_file)) {
            $versioned_files = json_decode(file_get_contents($assets_file), true, 512, JSON_THROW_ON_ERROR);
        }

        return new App\Assets($libraries, $versioned_files);
    },

    // Synchronized (Cron) Tasks
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

    // Web Hooks
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
];
