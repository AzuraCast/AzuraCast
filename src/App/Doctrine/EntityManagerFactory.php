<?php
namespace App\Doctrine;

use Doctrine\DBAL\Types\Type;
use Interop\Container\ContainerInterface;

class EntityManagerFactory
{
    public static function create(ContainerInterface $di, $options)
    {
        if(empty($options))
            return false;

        // Register custom data types.
        if (!Type::hasType('json'))
            Type::addType('json', 'App\Doctrine\Type\Json');

        if (!Type::hasType('unixdatetime'))
            Type::addType('unixdatetime', 'App\Doctrine\Type\UnixDateTime');

        Type::overrideType('array', 'App\Doctrine\Type\SoftArray');
        Type::overrideType('datetime', 'App\Doctrine\Type\UTCDateTime');

        // Fetch and store entity manager.
        $config = new \Doctrine\ORM\Configuration;

        // Handling for class names specified as platform types.
        if (!empty($options['conn']['platform']))
        {
            $class_obj = new \ReflectionClass($options['conn']['platform']);
            $options['conn']['platform'] = $class_obj->newInstance();
        }

        // Special handling for the utf8mb4 type.
        if ($options['conn']['driver'] == 'pdo_mysql' && $options['conn']['charset'] == 'utf8mb4')
        {
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

        if (isset($options['conn']['debug']) && $options['conn']['debug'])
            $config->setSQLLogger(new \App\Doctrine\Logger\EchoSQL);

        $config->addFilter('softdelete', '\App\Doctrine\Filter\SoftDelete');
        $config->addCustomNumericFunction('RAND', '\App\Doctrine\Functions\Rand');

        $config->addCustomStringFunction('FIELD', 'DoctrineExtensions\Query\Mysql\Field');
        $config->addCustomStringFunction('IF', 'DoctrineExtensions\Query\Mysql\IfElse');

        $evm = new \Doctrine\Common\EventManager();
        $em = \Doctrine\ORM\EntityManager::create($options['conn'], $config, $evm);

        $em->getFilters()->enable("softdelete");

        // Workaround to allow ENUM types to exist as strings in Doctrine.
        $conn = $em->getConnection();
        $platform = $conn->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');

        $platform->markDoctrineTypeCommented('json');
        $platform->markDoctrineTypeCommented('unixdatetime');
        $platform->markDoctrineTypeCommented('binary_uuid');
        $platform->markDoctrineTypeCommented('ip_integer');

        $conn->connect();

        return $em;
    }
}