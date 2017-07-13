<?php
use Doctrine\DBAL\Types\Type;

return function (\Slim\Container $di, \App\Config $config) {

    // Override Slim handlers.
    $di['callableResolver'] = function ($di) {
        return new \App\Mvc\Resolver($di);
    };

    $di['errorHandler'] = function ($di) {
        return function ($request, $response, $exception) use ($di) {
            return \App\Mvc\ErrorHandler::handle($di, $request, $response, $exception);
        };
    };

    $di['notFoundHandler'] = function ($di) {
        return function ($request, $response) use ($di) {
            $view = $di['view'];
            $template = $view->render('system/error_pagenotfound');

            $body = $response->getBody();
            $body->write($template);

            return $response->withStatus(404)->withBody($body);
        };
    };

    // Configs
    $di['config'] = $config;

    // Database
    $di['em'] = function ($di) {
        try {
            $options = [
                'autoGenerateProxies' => (APP_APPLICATION_ENV == "development"),
                'proxyNamespace' => 'Proxy',
                'proxyPath' => APP_INCLUDE_TEMP . '/proxies',
                'modelPath' => APP_INCLUDE_BASE . '/models',
                'conn' => [
                    'driver' => 'pdo_mysql',
                    'host' => (APP_INSIDE_DOCKER) ? 'mariadb' : 'localhost',
                    'dbname' => 'azuracast',
                    'user' => 'azuracast',
                    'password' => (APP_INSIDE_DOCKER) ? 'azur4c457' : $_ENV['db_password'],
                    'charset' => 'utf8',
                    'driverOptions' => [
                        1002 => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
                    ],
                ]
            ];

            // Fetch and store entity manager.
            $config = new \Doctrine\ORM\Configuration;

            // Handling for class names specified as platform types.
            if (!empty($options['conn']['platform'])) {
                $class_obj = new \ReflectionClass($options['conn']['platform']);
                $options['conn']['platform'] = $class_obj->newInstance();
            }

            // Special handling for the utf8mb4 type.
            if ($options['conn']['driver'] == 'pdo_mysql' && $options['conn']['charset'] == 'utf8mb4') {
                $options['conn']['platform'] = new \App\Doctrine\Platform\MysqlUnicode;
            }

            $metadata_driver = $config->newDefaultAnnotationDriver($options['modelPath']);
            $config->setMetadataDriverImpl($metadata_driver);

            $cache = new \App\Doctrine\Cache($di['cache_driver']);

            $config->setMetadataCacheImpl($cache);
            $config->setQueryCacheImpl($cache);
            $config->setResultCacheImpl($cache);

            $config->setProxyDir($options['proxyPath']);
            $config->setProxyNamespace($options['proxyNamespace']);

            $config->setDefaultRepositoryClassName('\App\Doctrine\Repository');

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

    $di['db'] = function ($di) {
        return $di['em']->getConnection();
    };

    // Auth and ACL
    $di['auth'] = function ($di) {
        return new \App\Auth($di['session'], $di['em']->getRepository('Entity\User'));
    };

    $di['acl'] = function ($di) {
        return new \AzuraCast\Acl\StationAcl($di['em'], $di['auth']);
    };

    // Caching
    $di['cache_driver'] = function ($di) {

        $cache_driver = new \Stash\Driver\Redis([
            'servers' => [
                [
                    'server' => (APP_INSIDE_DOCKER) ? 'redis' : 'localhost',
                    'port' => 6379, // default: 6379
                ],
            ],

            // 'password'      => '', // Must be commented out to have no authentication
            'database' => 0,
        ]);

        // Register Stash as session handler if necessary.
        $pool = new \Stash\Pool($cache_driver);
        $pool->setNamespace(\App\Cache::getSitePrefix('session'));

        $session = new \Stash\Session($pool);
        \Stash\Session::registerHandler($session);

        return $cache_driver;

    };

    $di['cache'] = function ($di) {
        return new \App\Cache($di['cache_driver'], 'user');
    };

    // Register URL handler.
    $di['url'] = function ($di) {
        return new \App\Url($di);
    };

    // Register session service.
    $di['session'] = function ($di) {
        // Depends on cache driver.
        $di->get('cache_driver');

        return new \App\Session;
    };

    // Register CSRF prevention security token service.
    $di['csrf'] = function ($di) {
        return new \App\Csrf($di['session']);
    };

    // Register Flash notification service.
    $di['flash'] = function ($di) {
        return new \App\Flash($di['session']);
    };

    // InfluxDB
    $di['influx'] = function ($di) {
        $opts = [
            'host' => (APP_INSIDE_DOCKER) ? 'influxdb' : 'localhost',
            'port' => 8086,
        ];

        $influx = new \InfluxDB\Client($opts['host'], $opts['port']);

        return $influx->selectDB('stations');
    };

    // E-mail Messenger
    $di['messenger'] = function ($di) {
        return new \App\Messenger($di);
    };

    // Supervisord Interaction
    $di['supervisor'] = function ($di) {
        $guzzle_client = new \GuzzleHttp\Client();
        $client = new \fXmlRpc\Client(
            'http://'.(APP_INSIDE_DOCKER ? 'stations' : '127.0.0.1').':9001/RPC2',
            new \fXmlRpc\Transport\HttpAdapterTransport(
                new \Http\Message\MessageFactory\GuzzleMessageFactory(),
                new \Http\Adapter\Guzzle6\Client($guzzle_client)
            )
        );

        $connector = new \Supervisor\Connector\XmlRpc($client);
        $supervisor = new \Supervisor\Supervisor($connector);

        if (!$supervisor->isConnected()) {
            throw new \App\Exception(sprintf(_('Could not connect to supervisord. Try running %s in a terminal to restart the service.'), '`sudo service supervisor restart`'));
        }

        return $supervisor;
    };

    // Scheduled synchronization manager
    $di['sync'] = function ($di) {
        return new \AzuraCast\Sync($di);
    };

    // Currently logged in user
    $di['user'] = $di->factory(function ($di) {
        $auth = $di['auth'];

        if ($auth->isLoggedIn()) {
            return $auth->getLoggedInUser();
        } else {
            return null;
        }
    });

    $di['customization'] = $di->factory(function ($di) {
        return new \AzuraCast\Customization($di);
    });

    $di['view'] = $di->factory(function ($di) {
        $view = new \App\Mvc\View(APP_INCLUDE_BASE . '/templates');
        $view->setFileExtension('phtml');
        $view->addAppCommands($di);

        $view->addData([
            'di' => $di,
            'assets' => $di['assets'],
            'auth' => $di['auth'],
            'acl' => $di['acl'],
            'url' => $di['url'],
            'config' => $di['config'],
            'flash' => $di['flash'],
            'customization' => $di['customization'],
        ]);

        return $view;
    });

    $di['assets'] = function ($di) {

        return new class($di['url']) {
            /** @var \App\Url */
            protected $url;

            /** @var array */
            protected $assets;

            public function __construct(\App\Url $url) {
                $this->url = $url;

                $assets = [];
                $assets_file = APP_INCLUDE_STATIC.'/assets.json';
                if (file_exists($assets_file)) {
                    $assets = json_decode(file_get_contents($assets_file), true);
                }

                $this->assets = $assets;
            }

            public function getPath($asset) {
                return $this->url->content($this->assets[$asset] ?? $asset);
            }
        };

    };

    // Set up application and routing.
    $di['app'] = function ($di) {

        $app = new \Slim\App($di);

        // Remove trailing slash from all URLs when routing.
        $app->add(function (
            \Psr\Http\Message\RequestInterface $request,
            \Psr\Http\Message\ResponseInterface $response,
            callable $next
        ) {
            $uri = $request->getUri();
            $path = $uri->getPath();

            if ($path != '/' && substr($path, -1) == '/') {
                // permanently redirect paths with a trailing slash
                // to their non-trailing counterpart
                $uri = $uri->withPath(substr($path, 0, -1));

                return $response->withRedirect((string)$uri, 301);
            }

            return $next($request, $response);
        });

        foreach($di['modules'] as $module) {
            $module_routes = APP_INCLUDE_MODULES.'/'.$module.'/routes.php';
            if (file_exists($module_routes)) {
                call_user_func(include($module_routes), $app);
            }
        }

        return $app;
    };

};