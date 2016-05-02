<?php
/**
 * Application Settings
 */

$session_lifetime = 86400*1;

$config = array(
    // Application name
    'name'              => 'AzuraCast',

    'phpSettings'       => array(
        'display_startup_errors' => 0,
        'display_errors'        => 0,
        'log_errors'            => 1,
        'error_log'             => APP_INCLUDE_TEMP.'/php_errors.log',
        'error_reporting'       => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT,
        'session' => array(
            'use_only_cookies'  => 1,
            'gc_maxlifetime'    => $session_lifetime,
            'gc_probability'    => 1,
            'gc_divisor'        => 100,
            'cookie_lifetime'   => $session_lifetime,
            'hash_function'     => 'sha512',
            'hash_bits_per_character' => 4,
        ),
    ),
    
    'includePaths'      => array(
        APP_INCLUDE_LIB.'/ThirdParty',
    ),
    
    'pluginpaths'       => array(
        'DF\Application\Resource\\' => 'DF/Application/Resource',
    ),
    
    'autoload'          => array(
        'psr0'      => array(
            'App'       => APP_INCLUDE_LIB,
            'Entity'    => APP_INCLUDE_MODELS,
        ),
        'psr4'      => array(
            '\\Proxy\\'     => APP_INCLUDE_TEMP.'/proxies',
        ),
    ),

    'resources'         => array(
        /* RESOURCES: Locale */
        'locale'            => array(
            'default'           => 'en_US',
        ),

        /* RESOURCES: Doctrine ORM Layer */
        'doctrine'          => array(
            'autoGenerateProxies' => (APP_APPLICATION_ENV == "development"),
            'proxyNamespace'    => 'Proxy',
            'proxyPath'         => APP_INCLUDE_TEMP.'/proxies',
            'modelPath'         => APP_INCLUDE_MODELS,
        ),
    ),
);

/**
 * Development mode changes.
 */

if (APP_APPLICATION_ENV != 'production')
{
    $config['phpSettings']['display_startup_errors'] = 1;
    $config['phpSettings']['display_errors'] = 1;

    // Update if your local configuration differs.
    $config['base_url'] = '//localhost:8080';

    unset($config['api_url']);
    unset($config['upload_url']);
}

return $config;
