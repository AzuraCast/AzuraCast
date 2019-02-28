<?php
return function (\Azura\Container $di)
{
    // Override Slim handlers.
    $di['request'] = function (\Azura\Container $di) {
        return \App\Http\Request::createFromEnvironment($di->get('environment'));
    };

    $di['response'] = function (\Azura\Container $di) {
        $headers = new \Slim\Http\Headers(['Content-Type' => 'text/html; charset=UTF-8']);
        $response = new \App\Http\Response(200, $headers, null);

        return $response->withProtocolVersion($di->get('settings')['httpVersion']);
    };

    $di['router'] = function(\Azura\Container $container) {
        $routerCacheFile = $container->get('settings')[\Azura\Settings::SLIM_ROUTER_CACHE_FILE];
        $router = new \App\Http\Router();
        $router->setCacheFile($routerCacheFile);
        $router->setContainer($container);
        return $router;
    };

    $di[\App\Http\ErrorHandler::class] = function($di) {
        return new \App\Http\ErrorHandler(
            $di[\App\Acl::class],
            $di[\Monolog\Logger::class],
            $di['router'],
            $di[\Azura\Session::class],
            $di[\Azura\View::class]
        );
    };
    
    $di->addAlias('phpErrorHandler', \App\Http\ErrorHandler::class);
    $di->addAlias('errorHandler', \App\Http\ErrorHandler::class);

    $di['notFoundHandler'] = function ($di) {
        return function (\App\Http\Request $request, \App\Http\Response $response) use ($di) {
            /** @var \Azura\View $view */
            $view = $di[\Azura\View::class];

            return $view->renderToResponse($response->withStatus(404), 'system/error_pagenotfound');
        };
    };

    $di[\App\Entity\Repository\SettingsRepository::class] = function($di) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        return new \App\Entity\Repository\SettingsRepository(
            $em,
            $em->getClassMetadata(\App\Entity\Settings::class)
        );
    };

    $di[\App\Entity\Repository\StationRepository::class] = function($di) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        return new \App\Entity\Repository\StationRepository(
            $em,
            $em->getClassMetadata(\App\Entity\Station::class),
            $di[\App\Sync\Task\Media::class],
            $di[\App\Radio\Adapters::class],
            $di[\App\Radio\Configuration::class],
            $di[\Azura\Cache::class]
        );
    };

    $di[\App\Entity\Repository\StationMediaRepository::class] = function($di) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        return new \App\Entity\Repository\StationMediaRepository(
            $em,
            $em->getClassMetadata(\App\Entity\StationMedia::class),
            $di[\App\Radio\Filesystem::class]
        );
    };

    $di[\App\Entity\Repository\StationPlaylistMediaRepository::class] = function($di) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        return new \App\Entity\Repository\StationPlaylistMediaRepository(
            $em,
            $em->getClassMetadata(\App\Entity\StationPlaylistMedia::class),
            $di[\Azura\Cache::class]
        );
    };

    $di[\App\Auth::class] = function ($di) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        /** @var App\Entity\Repository\UserRepository $user_repo */
        $user_repo = $em->getRepository(App\Entity\User::class);

        return new \App\Auth($di[\Azura\Session::class], $user_repo);
    };

    $di[\App\Acl::class] = function ($di) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        /** @var App\Entity\Repository\RolePermissionRepository $permissions_repo */
        $permissions_repo = $em->getRepository(App\Entity\RolePermission::class);

        return new \App\Acl($permissions_repo);
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
            throw new \Azura\Exception(sprintf('Could not connect to supervisord.'));
        }

        return $supervisor;
    };
    
    $di->extend(\Azura\View::class, function(\Azura\View $view, \Azura\Container $di) {
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
            return \App\Utilities::truncateText($text, $length);
        });
        $view->registerFunction('truncateUrl', function($url) {
            return \App\Utilities::truncateUrl($url);
        });

        $view->addData([
            'assets' => $di[\Azura\Assets::class],
            'auth' => $di[\App\Auth::class],
            'acl' => $di[\App\Acl::class],
            'customization' => $di[\App\Customization::class],
            'version' => $di[\App\Version::class],
        ]);

        return $view;
    });

    // MaxMind (IP Geolocation database for listener metadata)
    $di[\MaxMind\Db\Reader::class] = function($di) {
        $mmdb_path = dirname(APP_INCLUDE_ROOT).'/geoip/GeoLite2-City.mmdb';
        return new \MaxMind\Db\Reader($mmdb_path);
    };

    $di->extend(\Azura\EventDispatcher::class, function(\Azura\EventDispatcher $dispatcher, \Azura\Container $di) {
        if (isset($di[\App\Plugins::class])) {
            /** @var \App\Plugins $plugins */
            $plugins = $di[\App\Plugins::class];

            // Register plugin-provided events.
            $plugins->registerEvents($dispatcher);
        }

        return $dispatcher;
    });

    $di[\App\MessageQueue::class] = function($di) {
        // Build QueueFactory
        /** @var \Redis $redis */
        $redis = $di[\Redis::class];
        $redis->select(4);
        $driver = new \Bernard\Driver\PhpRedis\Driver($redis);

        $normalizer = new \Normalt\Normalizer\AggregateNormalizer([
            new \Bernard\Normalizer\EnvelopeNormalizer,
            new \Symfony\Component\Serializer\Normalizer\PropertyNormalizer
        ]);

        $symfony_serializer = new \Symfony\Component\Serializer\Serializer([$normalizer]);
        $serializer = new \Bernard\Serializer($normalizer);

        $queue_factory = new \Bernard\QueueFactory\PersistentFactory($driver, $serializer);

        // Event dispatcher
        $dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher;

        // Build Producer
        $producer = new \Bernard\Producer($queue_factory, $dispatcher);

        // Build Consumer
        $receivers = require __DIR__.'/messagequeue.php';
        $router = new \Bernard\Router\ReceiverMapRouter($receivers, new \Bernard\Router\ContainerReceiverResolver($di));

        $consumer = new Bernard\Consumer($router, $dispatcher);

        $mq = new \App\MessageQueue(
            $queue_factory,
            $producer,
            $consumer,
            $di[\Monolog\Logger::class]
        );

        $dispatcher->addSubscriber($mq);
        return $mq;
    };

    //
    // AzuraCast-specific dependencies
    //

    $di[\App\ApiUtilities::class] = function($di) {
        return new \App\ApiUtilities(
            $di[\Doctrine\ORM\EntityManager::class],
            $di['router'],
            $di[\App\Customization::class]
        );
    };

    $di[\Azura\Assets::class] = function ($di) {
        /** @var \Azura\Config $config */
        $config = $di[\Azura\Config::class];

        $libraries = $config->get('assets');

        $versioned_files = [];
        $assets_file = APP_INCLUDE_ROOT.'/web/static/assets.json';
        if (file_exists($assets_file)) {
            $versioned_files = json_decode(file_get_contents($assets_file), true);
        }

        return new \Azura\Assets($libraries, $versioned_files);
    };

    $di[\App\Customization::class] = function ($di) {
        return new \App\Customization(
            $di['settings'],
            $di[\App\Entity\Repository\SettingsRepository::class]
        );
    };

    $di[\App\Version::class] = function($di) {
        return new \App\Version(
            $di[\Azura\Cache::class],
            $di['settings']
        );
    };

    // Radio management
    $di->register(new \App\Provider\RadioProvider);

    // Synchronization tasks
    $di->register(new \App\Provider\SyncProvider);

    // Web Hooks
    $di->register(new \App\Provider\WebhookProvider);

    // Middleware
    $di->register(new \App\Provider\MiddlewareProvider);

    // Notifications
    $di->register(new \App\Provider\NotificationProvider);

    // Forms
    $di->register(new \App\Provider\FormProvider);

    // Controller groups
    $di->register(new \App\Provider\AdminProvider);
    $di->register(new \App\Provider\ApiProvider);
    $di->register(new \App\Provider\FrontendProvider);
    $di->register(new \App\Provider\StationsProvider);

    return $di;
};
