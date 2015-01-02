<?php
/**
 * Application Settings
 */

$session_lifetime = 86400*4;

$config = array(
    // Application name
    'name'              => 'Ponyville Live!',
    'analytics_code'    => 'UA-37359273-1',
    
    // Primary application web address
    'base_url'          => '//'.($_SERVER["HTTP_HOST"] ? $_SERVER["HTTP_HOST"] : 'ponyvillelive.com'),
    
    // DF Messenger mail settings
    'mail'              => array(
        'templates'         => DF_INCLUDE_BASE.'/messages',
        'from_addr'         => 'info@ponyvillelive.com',
        'from_name'         => 'Ponyville Live!',
        'use_smtp'          => true,
    ),

    'phpSettings'       => array(
        'display_startup_errors' => 0,
        'display_errors'        => 0,
        'log_errors'            => 1,
        'error_log'             => DF_INCLUDE_TEMP.'/php_errors.log',
        'error_reporting'       => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT,
        'session' => array(
            'use_only_cookies'  => 1,
            'save_path'         => DF_INCLUDE_TEMP.DIRECTORY_SEPARATOR.'sessions',
            'gc_maxlifetime'    => $session_lifetime,
            'gc_probability'    => 1,
            'gc_divisor'        => 100,
            'cookie_lifetime'   => $session_lifetime,
            'hash_function'     => 'sha512',
            'hash_bits_per_character' => 4,
        ),
    ),
        
    'bootstrap'         => array(
        'path'              => 'DF/Application/Bootstrap.php',
        'class'             => '\DF\Application\Bootstrap',
    ),
    
    'includePaths'      => array(
        DF_INCLUDE_LIB.'/ThirdParty',
    ),
    
    'pluginpaths'       => array(
        'DF\Application\Resource\\' => 'DF/Application/Resource',
    ),
    
    'autoload'          => array(
        'psr0'      => array(
            'DF'        => DF_INCLUDE_LIB,
            'PVL'       => DF_INCLUDE_LIB,
            'Entity'    => DF_INCLUDE_MODELS,
            'Hybrid'    => DF_INCLUDE_LIB.'/ThirdParty',
            'Hybrid_'   => DF_INCLUDE_LIB.'/ThirdParty/Hybrid',
            'tmhOAuth'  => DF_INCLUDE_LIB.'/ThirdParty',
        ),
        'psr4'      => array(
            '\\Proxy\\'     => DF_INCLUDE_TEMP.'/proxies',
        ),
    ),

    'resources'         => array(
        /* RESOURCES: Locale */
        'locale'            => array(
            'default'           => 'en_US',
        ),
        
        /* RESOURCES: Front Controller */
        'frontController'   => array(
            'throwerrors'       => true,
            'moduleDirectory'   => DF_INCLUDE_MODULES,
            'moduleControllerDirectoryName' => "controllers",
            'defaultModule'     => "default",
            'defaultAction'     => "index",
            'defaultControllerName' => "index",
        ),
        
        /* RESOURCES: Doctrine ORM Layer */
        'doctrine'          => array(
            'autoGenerateProxies' => (DF_APPLICATION_ENV == "development"),
            'proxyNamespace'    => 'Proxy',
            'proxyPath'         => DF_INCLUDE_TEMP.'/proxies',
            'modelPath'         => DF_INCLUDE_MODELS,
        ),
                
        /* RESOURCES: Menu */
        'menu'              => array(
            'enabled'           => true,
        ),
        
        /* RESOURCES: Layout */
        'layout'            => array(
            'layout'            => 'default',
            'layoutPath'        => DF_INCLUDE_APP.'/layouts',
            'commonTemplates'   => DF_INCLUDE_BASE.'/common',
        ),
    ),
);

/**
 * Development mode changes.
 */

if (DF_APPLICATION_ENV != 'production')
{
    $config['phpSettings']['display_startup_errors'] = 1;
    $config['phpSettings']['display_errors'] = 1;

    // Update if your local configuration differs.
    $config['base_url'] = '//dev.pvlive.me';
}

return $config;
