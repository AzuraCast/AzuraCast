<?php
return function (\Slim\Container $di, $settings)
{

    $di['app_settings'] = $settings;

    // Override Slim handlers.
    $di['request'] = function (\Slim\Container $di) {
        return \App\Http\Request::createFromEnvironment($di->get('environment'));
    };

    $di['response'] = function (\Slim\Container $di) {
        $headers = new \Slim\Http\Headers(['Content-Type' => 'text/html; charset=UTF-8']);
        $response = new \App\Http\Response(200, $headers, null);

        return $response->withProtocolVersion($di->get('settings')['httpVersion']);
    };

    $di['router'] = function(\Slim\Container $di) {
        $routerCacheFile = $di['settings']['routerCacheFile'];
        $router = (new \App\Http\Router())->setCacheFile($routerCacheFile);
        $router->setContainer($di);
        return $router;
    };

    $di['callableResolver'] = function ($di) {
        return new \App\Http\Resolver($di);
    };

    $di['errorHandler'] = function ($di) {
        return $di[\App\Http\ErrorHandler::class];
    };

    $di['phpErrorHandler'] = function($di) {
        return $di[\App\Http\ErrorHandler::class];
    };

    $di['notFoundHandler'] = function ($di) {
        return function (\App\Http\Request $request, \App\Http\Response $response) use ($di) {
            /** @var \App\View $view */
            $view = $di[App\View::class];

            return $view->renderToResponse($response->withStatus(404), 'system/error_pagenotfound');
        };
    };

    $di['foundHandler'] = function() {
        return new \Slim\Handlers\Strategies\RequestResponseArgs();
    };

    $di[\App\Config::class] = function () {
        return new \App\Config(__DIR__);
    };

    $di[\Doctrine\ORM\EntityManager::class] = function ($di) {
        try {
            $options = [
                'autoGenerateProxies' => !APP_IN_PRODUCTION,
                'proxyNamespace' => 'AppProxy',
                'proxyPath' => APP_INCLUDE_TEMP . '/proxies',
                'modelPath' => APP_INCLUDE_ROOT . '/src/Entity',
                'conn' => [
                    'driver' => 'pdo_mysql',
                    'charset' => 'utf8mb4',
                    'defaultTableOptions' => [
                        'charset' => 'utf8mb4',
                        'collate' => 'utf8mb4_unicode_ci',
                    ],
                    'driverOptions' => [
                        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
                    ],
                    'platform' => new \Doctrine\DBAL\Platforms\MariaDb1027Platform(),
                ]
            ];

            if (APP_INSIDE_DOCKER) {
                $options['conn']['host'] = $_ENV['MYSQL_HOST'] ?? 'mariadb';
                $options['conn']['port'] = $_ENV['MYSQL_PORT'] ?? 3306;
                $options['conn']['dbname'] = $_ENV['MYSQL_DATABASE'] ?? 'azuracast';
                $options['conn']['user'] = $_ENV['MYSQL_USER'] ?? 'azuracast';
                $options['conn']['password'] = $_ENV['MYSQL_PASSWORD'] ?? 'azur4c457';
            } else {
                $options['conn']['host'] = $_ENV['db_host'] ?? 'localhost';
                $options['conn']['port'] = $_ENV['db_port'] ?? '3306';
                $options['conn']['dbname'] = $_ENV['db_name'] ?? 'azuracast';
                $options['conn']['user'] = $_ENV['db_username'] ?? 'azuracast';
                $options['conn']['password'] = $_ENV['db_password'];
            }

            // Fetch and store entity manager.
            $config = new \Doctrine\ORM\Configuration;

            $metadata_driver = $config->newDefaultAnnotationDriver($options['modelPath']);
            $config->setMetadataDriverImpl($metadata_driver);

            if (APP_IN_PRODUCTION) {
                /** @var \Redis $redis */
                $redis = $di[\Redis::class];
                $redis->select(2);

                $cache = new \App\Doctrine\Cache\Redis;
                $cache->setRedis($redis);
            } else {
                $cache = new \Doctrine\Common\Cache\ArrayCache;
            }

            $config->setMetadataCacheImpl($cache);
            $config->setQueryCacheImpl($cache);
            $config->setResultCacheImpl($cache);

            // Disable second-level cache for unit testing purposes, as it causes data to be out of date on pages.
            if (APP_TESTING_MODE) {
                $config->setSecondLevelCacheEnabled(false);
            }

            $config->setProxyDir($options['proxyPath']);
            $config->setProxyNamespace($options['proxyNamespace']);
            $config->setAutoGenerateProxyClasses(\Doctrine\Common\Proxy\AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
            $config->setDefaultRepositoryClassName(\App\Entity\Repository\BaseRepository::class);

            if (isset($options['conn']['debug']) && $options['conn']['debug']) {
                $config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
            }

            $config->addCustomNumericFunction('RAND', '\App\Doctrine\Functions\Rand');
            $config->addCustomStringFunction('FIELD', 'DoctrineExtensions\Query\Mysql\Field');
            $config->addCustomStringFunction('IF', 'DoctrineExtensions\Query\Mysql\IfElse');

            $em = \Doctrine\ORM\EntityManager::create($options['conn'], $config, new \Doctrine\Common\EventManager);

            return $em;
        } catch (\Exception $e) {
            throw new \App\Exception\Bootstrap($e->getMessage());
        }
    };

    $di[\Doctrine\DBAL\Connection::class] = function ($di) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];
        return $em->getConnection();
    };

    $di[\App\Entity\Repository\SettingsRepository::class] = function($di) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        return $em->getRepository(App\Entity\Settings::class);
    };

    $di[\App\Auth::class] = function ($di) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        /** @var App\Entity\Repository\UserRepository $user_repo */
        $user_repo = $em->getRepository(App\Entity\User::class);

        return new \App\Auth($di[\App\Session::class], $user_repo);
    };

    $di[\App\Acl::class] = function ($di) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        /** @var App\Entity\Repository\RolePermissionRepository $permissions_repo */
        $permissions_repo = $em->getRepository(App\Entity\RolePermission::class);

        return new \App\Acl($permissions_repo);
    };

    $di[\Redis::class] = $di->factory(function ($di) {
        $redis_host = (APP_INSIDE_DOCKER) ? 'redis' : 'localhost';

        $redis = new \Redis();
        $redis->connect($redis_host, 6379, 15);
        return $redis;
    });

    $di[\App\Cache::class] = function ($di) {
        /** @var \Redis $redis */
        $redis = $di[\Redis::class];
        $redis->select(0);

        return new \App\Cache($redis);
    };

    $di[\App\Session::class] = function ($di) {
        if (!APP_TESTING_MODE) {
            ini_set('session.gc_maxlifetime', 86400);
            ini_set('session.gc_probability', 1);
            ini_set('session.gc_divisor', 100);

            $redis_server = (APP_INSIDE_DOCKER) ? 'redis' : 'localhost';
            ini_set('session.save_handler', 'redis');
            ini_set('session.save_path', 'tcp://' . $redis_server . ':6379?database=1');
        }

        return new \App\Session;
    };

    $di[\InfluxDB\Database::class] = function ($di) {
        $opts = [
            'host' => (APP_INSIDE_DOCKER) ? 'influxdb' : 'localhost',
            'port' => 8086,
        ];

        $influx = new \InfluxDB\Client($opts['host'], $opts['port']);

        return $influx->selectDB('stations');
    };

    $di[\Supervisor\Supervisor::class] = function ($di) {
        $guzzle_client = new \GuzzleHttp\Client();
        $client = new \fXmlRpc\Client(
            'http://' . (APP_INSIDE_DOCKER ? 'stations' : '127.0.0.1') . ':9001/RPC2',
            new \fXmlRpc\Transport\HttpAdapterTransport(
                new \Http\Message\MessageFactory\GuzzleMessageFactory(),
                new \Http\Adapter\Guzzle6\Client($guzzle_client)
            )
        );

        $connector = new \Supervisor\Connector\XmlRpc($client);
        $supervisor = new \Supervisor\Supervisor($connector);

        if (!$supervisor->isConnected()) {
            throw new \App\Exception(sprintf('Could not connect to supervisord.'));
        }

        return $supervisor;
    };

    $di[\App\View::class] = $di->factory(function(\Slim\Container $di) {
        $view = new App\View(dirname(__DIR__) . '/resources/templates');
        $view->setFileExtension('phtml');

        $view->registerFunction('service', function($service) {
            return $this->di->get($service);
        });

        $view->registerFunction('escapeJs', function($string) {
            return json_encode($string);
        });

        $view->registerFunction('mailto', function ($address, $link_text = null) {
            $address = substr(chunk_split(bin2hex(" $address"), 2, ";&#x"), 3, -3);
            $link_text = $link_text ?? $address;

            return '<a href="mailto:' . $address . '">' . $link_text . '</a>';
        });

        $view->registerFunction('pluralize', function ($word, $num = 0) {
            if ((int)$num === 1) {
                return $word;
            } else {
                return \Doctrine\Common\Inflector\Inflector::pluralize($word);
            }
        });

        $view->registerFunction('truncate', function ($text, $length = 80) {
            return \App\Utilities::truncate_text($text, $length);
        });

        /** @var \App\Session $session */
        $session = $di[\App\Session::class];

        $view->addData([
            'app_settings' => $di['app_settings'],
            'router' => $di['router'],
            'request' => $di['request'],
            'assets' => $di[\App\Assets::class],
            'auth' => $di[\App\Auth::class],
            'acl' => $di[\App\Acl::class],
            'flash' => $session->getFlash(),
            'customization' => $di[\App\Customization::class],
        ]);

        /** @var \App\EventDispatcher $dispatcher */
        $dispatcher = $di[\App\EventDispatcher::class];
        $dispatcher->dispatch(\App\Event\BuildView::NAME, new \App\Event\BuildView($view));

        return $view;
    });

    $di[\App\Http\ErrorHandler::class] = function($di) {
        return new \App\Http\ErrorHandler(
            $di[\App\Acl::class],
            $di[\Monolog\Logger::class],
            $di['router'],
            $di[\App\Session::class],
            $di[\App\View::class]
        );
    };

    $di[\Monolog\Logger::class] = function($di) use ($settings) {
        $logger = new Monolog\Logger($settings['name']);

        if (APP_INSIDE_DOCKER || APP_IS_COMMAND_LINE) {
            $logging_level = (APP_IN_PRODUCTION) ? \Monolog\Logger::WARNING : \Monolog\Logger::DEBUG;

            $handler = new \Monolog\Handler\StreamHandler('php://stderr', $logging_level, true);
            $logger->pushHandler($handler);
        } else {
            $handler = new \Monolog\Handler\StreamHandler(APP_INCLUDE_TEMP.'/azuracast.log', \Monolog\Logger::WARNING, true);
            $logger->pushHandler($handler);
        }

        return $logger;
    };

    $di[\MaxMind\Db\Reader::class] = function($di) {
        $mmdb_path = dirname(APP_INCLUDE_ROOT).'/geoip/GeoLite2-City.mmdb';
        return new \MaxMind\Db\Reader($mmdb_path);
    };

    $di[\GuzzleHttp\Client::class] = function($di) {
        $stack = \GuzzleHttp\HandlerStack::create();

        $stack->unshift(function (callable $handler) use ($di) {
            return function (\Psr\Http\Message\RequestInterface $request, array $options) use ($handler, $di) {
                if ($request->getUri()->getScheme() === 'https') {
                    $fetcher = new \ParagonIE\Certainty\RemoteFetch(APP_INCLUDE_TEMP);
                    $latestCACertBundle = $fetcher->getLatestBundle();

                    $options['verify'] = $latestCACertBundle->getFilePath();
                }

                return $handler($request, $options);
            };
        }, 'ssl_verify');

        $stack->push(\GuzzleHttp\Middleware::log(
            $di[\Monolog\Logger::class],
            new \GuzzleHttp\MessageFormatter('HTTP client {method} call to {uri} produced response {code}'),
            \Monolog\Logger::DEBUG
        ));

        return new \GuzzleHttp\Client([
            'handler' => $stack,
            'http_errors' => false,
            'timeout' => 3.0,
        ]);
    };

    $di[\App\EventDispatcher::class] = function($di) use ($settings) {
        $dispatcher = new \App\EventDispatcher($di);

        // Register application default events.
        call_user_func(include(__DIR__.'/events.php'), $dispatcher);

        /** @var \App\Plugins $plugins */
        $plugins = $di[\App\Plugins::class];

        // Register plugin-provided events.
        $plugins->registerEvents($dispatcher);

        return $dispatcher;
    };

    //
    // AzuraCast-specific dependencies
    //

    $di[\App\ApiUtilities::class] = function($di) {
        return new \App\ApiUtilities(
            $di[\Doctrine\ORM\EntityManager::class],
            $di['router']
        );
    };

    $di[\App\Assets::class] = function ($di) {
        /** @var \App\Config $config */
        $config = $di[\App\Config::class];

        $libraries = $config->get('assets');

        $versioned_files = [];
        $assets_file = APP_INCLUDE_STATIC . '/assets.json';
        if (file_exists($assets_file)) {
            $versioned_files = json_decode(file_get_contents($assets_file), true);
        }

        return new \App\Assets($libraries, $versioned_files);
    };

    $di[\App\Customization::class] = function ($di) {
        return new \App\Customization(
            $di['app_settings'],
            $di[\App\Entity\Repository\SettingsRepository::class]
        );
    };

    $di[\App\RateLimit::class] = function($di) {
        /** @var \Redis $redis */
        $redis = $di[\Redis::class];
        $redis->select(3);

        return new \App\RateLimit($redis);
    };

    // Radio management
    $di->register(new \App\Provider\RadioProvider);

    // Synchronization tasks
    $di->register(new \App\Provider\SyncProvider);

    // Web Hooks
    $di->register(new \App\Provider\WebhookProvider);

    // Middleware
    $di->register(new \App\Provider\MiddlewareProvider);

    // Event Handlers
    $di->register(new \App\Provider\EventHandlerProvider);

    // Controller groups
    $di->register(new \App\Provider\AdminProvider);
    $di->register(new \App\Provider\ApiProvider);
    $di->register(new \App\Provider\FrontendProvider);
    $di->register(new \App\Provider\StationsProvider);

    // Main Slim Application
    $di['app'] = function ($di) {

        $app = new \Slim\App($di);

        /** @var \App\EventDispatcher $dispatcher */
        $dispatcher = $di[\App\EventDispatcher::class];
        $dispatcher->dispatch(\App\Event\BuildRoutes::NAME, new \App\Event\BuildRoutes($app));

        return $app;
    };

    return $di;

};
