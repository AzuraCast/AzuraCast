<?php
namespace DF\Application\Resource;

use \Doctrine\Common\ClassLoader;
use \Doctrine\DBAL\Types\Type;

class Doctrine extends \Zend_Application_Resource_ResourceAbstract
{
    protected $_em;
    
    public function init()
    {
        $options = $this->getOptions();
        
        if(empty($options))
            return;
        
        // Register custom data types.
        Type::addType('json', 'DF\Doctrine\Type\Json');
        Type::addType('unixdatetime', 'DF\Doctrine\Type\UnixDateTime');
        Type::overrideType('array', 'DF\Doctrine\Type\SoftArray');
        Type::overrideType('datetime', 'DF\Doctrine\Type\UTCDateTime');
        
        // Fetch and store entity manager.
        $this->_em = $this->_getEntityManager();
        
        $conn = $this->_em->getConnection();
        $platform = $conn->getDatabasePlatform();
        
        $platform->markDoctrineTypeCommented(Type::getType('json'));
        $platform->markDoctrineTypeCommented(Type::getType('unixdatetime'));
        
        \Zend_Registry::set('em', $this->_em);
        return $this->_em;
    }
    
    protected function _getEntityManager()
    {
        $options = $this->getOptions();
        $config = new \Doctrine\ORM\Configuration;

        $app_config = \Zend_Registry::get('config');
        $options['conn'] = $app_config->db->toArray();
        
        if ($options['conn']['platform'])
        {
			$class_obj = new \ReflectionClass($options['conn']['platform']);
			$options['conn']['platform'] = $class_obj->newInstance();
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
        
        if ($options['conn']['driver'] != "pdo_sqlsrv")
			$em->getConnection()->setCharset('utf8');
        
        $em->getFilters()->enable("softdelete");
        
        // Trigger proxy regeneration.
        if ($regen_proxies)
        {
			$metadatas = $em->getMetadataFactory()->getAllMetadata();
            $em->getProxyFactory()->generateProxyClasses($metadatas);
        }
        
        // Try the connection before rendering the page.
        try
        {
			$em->getConnection()->connect();
		}
        catch(\Exception $e)
        {
			$db_config_location = str_replace(DF_INCLUDE_ROOT, '', DF_INCLUDE_APP).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'application.conf.php';
		
			\DF\Application\Maintenance::display('
				<h2>Database Error</h2>
				<p>The system could not connect to the database. Verify that the information listed in "<i>'.$db_config_location.'</i>" is correct.</p>
				<blockquote>'.$e->getMessage().'</blockquote>
			');
			exit;
		}
		
        return $em;
    }
}