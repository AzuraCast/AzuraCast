<?php

return [
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
            $defaults['conn']['dbname'] = $_ENV['db_name'] ?? 'app';
            $defaults['conn']['user'] = $_ENV['db_username'] ?? 'app';
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
];