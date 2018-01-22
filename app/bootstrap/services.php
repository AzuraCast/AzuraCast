<?php
return function (\Slim\Container $di, $settings) {

    $di['app_settings'] = $settings;

    // Override Slim handlers.
    $di['callableResolver'] = function ($di) {
        return new \App\Mvc\Resolver($di);
    };

    $di['errorHandler'] = function ($di) {
        return function ($request, $response, $exception) use ($di) {
            return \App\Mvc\ErrorHandler::handle($di, $request, $response, $exception);
        };
    };

    $di['phpErrorHandler'] = function($di) {
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
    $di[\App\Config::class] = function ($di) {
        return new \App\Config(APP_INCLUDE_BASE . '/config', $di);
    };

    // Database
    $di[\Doctrine\ORM\EntityManager::class] = function ($di) {
        try {
            $options = [
                'autoGenerateProxies' => !APP_IN_PRODUCTION,
                'proxyNamespace' => 'AppProxy',
                'proxyPath' => APP_INCLUDE_TEMP . '/proxies',
                'modelPath' => APP_INCLUDE_BASE . '/models',
                'conn' => [
                    'driver' => 'pdo_mysql',
                    'charset' => 'utf8mb4',
                    'driverOptions' => [
                        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
                    ],
                ]
            ];

            if (APP_INSIDE_DOCKER) {
                $options['conn']['host'] = 'mariadb';
                $options['conn']['port'] = 3306;
                $options['conn']['dbname'] = 'azuracast';
                $options['conn']['user'] = 'azuracast';
                $options['conn']['password'] = 'azur4c457';
            } else {
                $options['conn']['host'] = $_ENV['db_host'] ?? 'localhost';
                $options['conn']['port'] = $_ENV['db_port'] ?? '3306';
                $options['conn']['dbname'] = $_ENV['db_name'] ?? 'azuracast';
                $options['conn']['user'] = $_ENV['db_username'] ?? 'azuracast';
                $options['conn']['password'] = $_ENV['db_password'];
            }

            \Doctrine\Common\Proxy\Autoloader::register($options['proxyPath'], $options['proxyNamespace']);

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

            $config->setProxyDir($options['proxyPath']);
            $config->setProxyNamespace($options['proxyNamespace']);
            $config->setAutoGenerateProxyClasses(\Doctrine\Common\Proxy\AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
            $config->setDefaultRepositoryClassName(\Entity\Repository\BaseRepository::class);

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
        $em = $di['em'];
        return $em->getConnection();
    };

    // Auth and ACL
    $di[\App\Auth::class] = function ($di) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        /** @var Entity\Repository\UserRepository $user_repo */
        $user_repo = $em->getRepository(Entity\User::class);

        return new \App\Auth($di[\App\Session::class], $user_repo);
    };

    // Access control list (ACL)
    $di[\AzuraCast\Acl\StationAcl::class] = function ($di) {
        return new \AzuraCast\Acl\StationAcl($di[\Doctrine\ORM\EntityManager::class], $di[\App\Auth::class]);
    };

    $di[\App\Acl::class] = function($di) {
        return $di[\AzuraCast\Acl\StationAcl::class];
    };

    // Caching
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

    // Register URL handler.
    $di[\App\Url::class] = function ($di) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $em->getRepository(\Entity\Settings::class);

        $base_url = $settings_repo->getSetting('base_url', '');
        $prefer_browser_url = (bool)$settings_repo->getSetting('prefer_browser_url', 0);

        $http_host = $_SERVER['HTTP_HOST'] ?? '';
        $ignore_hosts = ['localhost', 'nginx'];

        if (!empty($http_host) && !in_array($http_host, $ignore_hosts) && ($prefer_browser_url || empty($base_url))) {
            $base_url = $http_host;
        }

        if (!empty($base_url)) {
            $always_use_ssl = (bool)$settings_repo->getSetting('always_use_ssl', 0);
            $base_url_schema = (APP_IS_SECURE || $always_use_ssl) ? 'https://' : 'http://';

            $base_url = $base_url_schema.$base_url;
        }

        return new \App\Url($di['router'], $base_url);
    };

    // Register session service.
    $di[\App\Session::class] = function ($di) {
        ini_set('session.gc_maxlifetime', 86400);
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 100);

        $redis_server = (APP_INSIDE_DOCKER) ? 'redis' : 'localhost';
        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', 'tcp://' . $redis_server . ':6379?database=1');

        return new \App\Session;
    };

    // Register CSRF prevention security token service.
    $di[\App\Csrf::class] = function ($di) {
        return new \App\Csrf($di[\App\Session::class]);
    };

    // Register Flash notification service.
    $di[\App\Flash::class] = function ($di) {
        return new \App\Flash($di[\App\Session::class]);
    };

    // InfluxDB
    $di[\InfluxDB\Database::class] = function ($di) {
        $opts = [
            'host' => (APP_INSIDE_DOCKER) ? 'influxdb' : 'localhost',
            'port' => 8086,
        ];

        $influx = new \InfluxDB\Client($opts['host'], $opts['port']);

        return $influx->selectDB('stations');
    };

    // Supervisord Interaction
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
            throw new \App\Exception(sprintf(_('Could not connect to supervisord. Try running %s in a terminal to restart the service.'),
                '`sudo service supervisor restart`'));
        }

        return $supervisor;
    };

    // Scheduled synchronization manager
    $di[\AzuraCast\Sync::class] = function ($di) {
        return new \AzuraCast\Sync($di);
    };

    // Site-wide user-based customization.
    $di[\AzuraCast\Customization::class] = function ($di) {

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $di[\Doctrine\ORM\EntityManager::class];
        $settings_repo = $em->getRepository(Entity\Settings::class);

        return new \AzuraCast\Customization($di['app_settings'], $di['user'], $settings_repo);
    };

    // Main view/template renderer.
    $di[\App\Mvc\View::class] = $di->factory(function (\Slim\Container $di) {
        $view = new \App\Mvc\View(APP_INCLUDE_BASE . '/templates');
        $view->setFileExtension('phtml');

        $view->loadExtension(new \App\Mvc\View\Paginator($di[\App\Url::class]));

        $view->registerFunction('service', function($service) use ($di) {
            return $di->get($service);
        });

        $view->registerFunction('escapeJs', function($string) {
            return json_encode($string);
        });

        $view->registerFunction('mailto', function ($address, $link_text = null) {
            $address = substr(chunk_split(bin2hex(" $address"), 2, ";&#x"), 3, -3);
            $link_text = (is_null($link_text)) ? $address : $link_text;

            return '<a href="mailto:' . $address . '">' . $link_text . '</a>';
        });

        $view->registerFunction('pluralize', function ($word, $num = 0) {
            if ((int)$num == 1) {
                return $word;
            } else {
                return \Doctrine\Common\Inflector\Inflector::pluralize($word);
            }
        });

        $view->registerFunction('truncate', function ($text, $length = 80) {
            return \App\Utilities::truncate_text($text, $length);
        });

        $view->addData([
            'assets' => $di[\AzuraCast\Assets::class],
            'auth' => $di[\App\Auth::class],
            'acl' => $di[\AzuraCast\Acl\StationAcl::class],
            'url' => $di[\App\Url::class],
            'flash' => $di[\App\Flash::class],
            'customization' => $di[\AzuraCast\Customization::class],
            'app_settings' => $di['app_settings'],
        ]);

        return $view;
    });

    // Asset management
    $di[\AzuraCast\Assets::class] = function ($di) {
        $libraries = require('assets.php');

        $versioned_files = [];
        $assets_file = APP_INCLUDE_STATIC . '/assets.json';
        if (file_exists($assets_file)) {
            $versioned_files = json_decode(file_get_contents($assets_file), true);
        }

        return new \AzuraCast\Assets($libraries, $versioned_files, $di[\App\Url::class]);
    };

    // Rate limit checking
    $di[\AzuraCast\RateLimit::class] = function($di) {
        /** @var \Redis $redis */
        $redis = $di[\Redis::class];
        $redis->select(3);

        return new \AzuraCast\RateLimit($redis);
    };

    // Currently logged in user
    $di['user'] = function ($di) {
        /** @var \App\Auth $auth */
        $auth = $di[\App\Auth::class];

        if ($auth->isLoggedIn()) {
            return $auth->getLoggedInUser();
        } else {
            return null;
        }
    };

    // Set up application and routing.
    $di['app'] = function ($di) {

        $app = new \Slim\App($di);

        // Remove trailing slash from all URLs when routing.
        $app->add(function (
            \Slim\Http\Request $request,
            \Slim\Http\Response $response,
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

        // Check HTTPS setting and enforce Content Security Policy accordingly.
        $app->add(function (
            \Slim\Http\Request $request,
            \Slim\Http\Response $response,
            callable $next
        ) {
            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $this->get(\Doctrine\ORM\EntityManager::class);

            /** @var \Entity\Repository\SettingsRepository $settings_repo */
            $settings_repo = $em->getRepository(\Entity\Settings::class);

            $always_use_ssl = (bool)$settings_repo->getSetting('always_use_ssl', 0);
            $internal_api_url = mb_stripos($request->getUri()->getPath(), '/api/internal') === 0;

            $uri = $request->getUri();
            $uri_is_https = ($uri->getScheme() === 'https');

            // Assemble Content Security Policy (CSP)
            $csp = [];

            /** @var \AzuraCast\Assets $assets */
            $assets = $this->get(\AzuraCast\Assets::class);

            // CSP JavaScript policy
            // Note: unsafe-eval included for Vue template compiling
            $csp[] = "script-src https://maps.googleapis.com https://cdnjs.cloudflare.com 'self' 'unsafe-eval' 'nonce-".$assets->getCspNonce()."'";

            if ($uri_is_https) {

                $csp[] = 'upgrade-insecure-requests';

            } elseif ($always_use_ssl && !$internal_api_url) {

                // Enforce secure cookies.
                ini_set('session.cookie_secure', 1);

                // Redirect if URL is not currently secure.
                if (!$uri_is_https) {
                    if (!$uri->getPort()) {
                        $uri = $uri->withPort(443);
                    }
                    return $response->withRedirect((string)$uri->withScheme('https'), 302);
                }

                // Set HSTS header.
                $response = $response->withHeader('Strict-Transport-Security', 'max-age=3600');

                $csp[] = 'upgrade-insecure-requests';
            }

            $response = $response->withHeader('Content-Security-Policy', implode('; ', $csp));

            return $next($request, $response);
        });

        foreach ($di['modules'] as $module) {
            $module_routes = APP_INCLUDE_MODULES . '/' . $module . '/routes.php';
            if (file_exists($module_routes)) {
                call_user_func(include($module_routes), $app);
            }
        }

        return $app;
    };

};