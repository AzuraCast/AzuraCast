<?php
namespace App\Service;

use \Doctrine\Common\ClassLoader;
use \Doctrine\DBAL\Types\Type;

class Doctrine
{
    public static function init($options)
    {
        if(empty($options))
            return false;

        // Register custom data types.
        Type::addType('json', 'App\Doctrine\Type\Json');
        Type::addType('unixdatetime', 'App\Doctrine\Type\UnixDateTime');
        Type::addType('binary_uuid', 'App\Doctrine\Type\BinaryUuid');
        Type::addType('ip_integer', 'App\Doctrine\Type\IpAddrInteger');

        Type::overrideType('array', 'App\Doctrine\Type\SoftArray');
        Type::overrideType('datetime', 'App\Doctrine\Type\UTCDateTime');

        // Fetch and store entity manager.
        $em = self::getEntityManager($options);

        $conn = $em->getConnection();
        $platform = $conn->getDatabasePlatform();

        $platform->markDoctrineTypeCommented('json');
        $platform->markDoctrineTypeCommented('unixdatetime');
        $platform->markDoctrineTypeCommented('binary_uuid');
        $platform->markDoctrineTypeCommented('ip_integer');

        $conn->connect();

        return $em;
    }

    protected static function getEntityManager($options)
    {
        $config = new \Doctrine\ORM\Configuration;

        // Handling for class names specified as platform types.
        if ($options['conn']['platform'])
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

        $cache = new \App\Doctrine\Cache;
        // $cache->setNamespace('doctrine_');

        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setResultCacheImpl($cache);

        $config->setProxyDir($options['proxyPath']);
        $config->setProxyNamespace($options['proxyNamespace']);

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
        $platform = $em->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');

        return $em;
    }
}