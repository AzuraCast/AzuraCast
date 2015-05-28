<?php
namespace DF\Phalcon\Service;

use \Doctrine\Common\ClassLoader;
use \Doctrine\DBAL\Types\Type;

class Doctrine
{
    public static function init($options)
    {
        if(empty($options))
            return false;

        // Register custom data types.
        Type::addType('json', 'DF\Doctrine\Type\Json');
        Type::addType('unixdatetime', 'DF\Doctrine\Type\UnixDateTime');
        Type::overrideType('array', 'DF\Doctrine\Type\SoftArray');
        Type::overrideType('datetime', 'DF\Doctrine\Type\UTCDateTime');

        // Fetch and store entity manager.
        $em = self::getEntityManager($options);

        $conn = $em->getConnection();
        $platform = $conn->getDatabasePlatform();

        $platform->markDoctrineTypeCommented(Type::getType('json'));
        $platform->markDoctrineTypeCommented(Type::getType('unixdatetime'));

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
            $options['conn']['platform'] = new \DF\Doctrine\Platform\MysqlUnicode;
        }

        $metadata_driver = $config->newDefaultAnnotationDriver($options['modelPath']);
        $config->setMetadataDriverImpl($metadata_driver);

        $cache = new \DF\Doctrine\Cache;
        // $cache->setNamespace('doctrine_');

        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setResultCacheImpl($cache);

        $config->setProxyDir($options['proxyPath']);
        $config->setProxyNamespace($options['proxyNamespace']);

        if (!$regen_proxies)
            $config->setAutoGenerateProxyClasses($options['autoGenerateProxies']);

        if (isset($options['conn']['debug']) && $options['conn']['debug'])
            $config->setSQLLogger(new \DF\Doctrine\Logger\EchoSQL);

        $config->addFilter('softdelete', '\DF\Doctrine\Filter\SoftDelete');
        $config->addCustomNumericFunction('RAND', '\DF\Doctrine\Functions\Rand');

        $evm = new \Doctrine\Common\EventManager();
        $em = \Doctrine\ORM\EntityManager::create($options['conn'], $config, $evm);

        $em->getFilters()->enable("softdelete");

        // Try the connection before rendering the page.
        $em->getConnection()->connect();

        return $em;
    }
}