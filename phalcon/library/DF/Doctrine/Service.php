<?php
namespace DF\Doctrine;

use \Doctrine\Common\ClassLoader;
use \Doctrine\DBAL\Types\Type;

class Service
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
        $em = self::getEntityManager();

        $conn = $em->getConnection();
        $platform = $conn->getDatabasePlatform();

        $platform->markDoctrineTypeCommented(Type::getType('json'));
        $platform->markDoctrineTypeCommented(Type::getType('unixdatetime'));

        return $em;
    }

    protected static function getEntityManager()
    {
        $options = $this->getOptions();
        $config = new \Doctrine\ORM\Configuration;

        $app_config = \Zend_Registry::get('config');
        $options['conn'] = $app_config->db->toArray();

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

        $regen_proxies = FALSE;
        if (DF_APPLICATION_ENV == "production" && !DF_IS_COMMAND_LINE)
        {
            $cache = new \DF\Doctrine\Cache;
            $cache->setNamespace('doctrine_');

            // Clear cache in case of updated production code.
            $upload_reference_path = DF_INCLUDE_BASE.DIRECTORY_SEPARATOR . '.env';
            $update_reference_path = DF_INCLUDE_BASE.DIRECTORY_SEPARATOR . '.updated';

            if (!file_exists($update_reference_path))
            {
                @file_put_contents($update_reference_path, 'This file is automatically modified to track proxy regeneration.');
                @touch($upload_reference_path);
            }

            clearstatcache();
            $last_upload_time = (int)@filemtime($upload_reference_path);
            $last_update_time = (int)@filemtime($update_reference_path);

            if ($last_upload_time >= $last_update_time)
            {
                @touch($update_reference_path);

                // Flush the cache.
                $cache->flushAll();

                // Clear the proxy directory.
                $proxy_dir = $options['proxyPath'];
                @mkdir($proxy_dir);

                $files = glob($proxy_dir.DIRECTORY_SEPARATOR.'*.php');
                foreach((array)$files as $file)
                    @unlink($file);

                // Trigger proxy regeneration below.
                $regen_proxies = TRUE;
                $config->setAutoGenerateProxyClasses(TRUE);
            }
        }
        else
        {
            $cache = new \Doctrine\Common\Cache\ArrayCache;
        }

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

        // Trigger proxy regeneration.
        if ($regen_proxies)
        {
            $metadatas = $em->getMetadataFactory()->getAllMetadata();
            $em->getProxyFactory()->generateProxyClasses($metadatas);
        }

        // Try the connection before rendering the page.
        $em->getConnection()->connect();

        return $em;
    }
}