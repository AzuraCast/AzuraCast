<?php
/**
 * Application Settings
 */

$session_lifetime = 86400*7;

$config = array(
	// Application name
	'name'				=> 'Ponyville Live!',
	'analytics_code'	=> 'UA-37359273-1',
	
	// Primary application web address
	'base_url'			=> (DF_IS_SECURE ? 'https' : 'http').'://'.($_SERVER["HTTP_HOST"] ? $_SERVER["HTTP_HOST"] : 'ponyvillelive.com'),
	
	// DF Messenger mail settings
    'mail'				=> array(
        'templates'			=> DF_INCLUDE_BASE.'/messages',
        'from_addr'         => 'info@ponyvillelive.com',
        'from_name'         => 'Ponyville Live!',
        // 'bounce_addr'		=> 'noreply@ponyvillelive.com',
        // 'error_addr'        => 'noreply@ponyvillelive.com',

        'use_smtp'			=> true,
        'smtp'				=> array(
        	'server'		=> 'smtp.mandrillapp.com',
        	'port'			=> '587',
        	'auth'			=> 'login',
        	'username'		=> 'loobalightdark@gmail.com',
        	'password'		=> 'd05MxdhMxGxq7i8HRh8_mg',
        ),
    ),

	'phpSettings'		=> array(
		'display_startup_errors'		=> 0,
		'display_errors'				=> 0,
		'error_reporting' => E_ALL & ~E_NOTICE & ~E_WARNING,
		'session' => array(
			'save_path' => DF_INCLUDE_TEMP.DIRECTORY_SEPARATOR.'sessions',
			'gc_maxlifetime' => $session_lifetime,
			'cookie_lifetime' => $session_lifetime,
		),
	),
		
	'bootstrap'			=> array(
		'path'				=> 'DF/Application/Bootstrap.php',
		'class'				=> '\DF\Application\Bootstrap',
	),
	
	'includePaths'		=> array(
		DF_INCLUDE_LIB,
        DF_INCLUDE_LIB.'/Doctrine',
    ),
	
	'pluginpaths'		=> array(
		'DF\Application\Resource\\' => 'DF/Application/Resource',
	),
    
    'autoload'          => array(
		'Zend_'		=> DF_INCLUDE_LIB,
		'Hybrid_'	=> DF_INCLUDE_LIB.'/ThirdParty',
        'DF'		=> DF_INCLUDE_LIB,
		'Doctrine'	=> DF_INCLUDE_LIB,
        'Symfony'	=> DF_INCLUDE_LIB.'/Doctrine',
        'PVL' 		=> DF_INCLUDE_LIB_LOCAL,
	),

	'resources'			=> array(
		/* RESOURCES: Locale */
		'locale'			=> array(
			'default'			=> 'en_US',
		),
		
		/* RESOURCES: Front Controller */
		'frontController'	=> array(
			'throwerrors'		=> true,
			'moduleDirectory'	=> DF_INCLUDE_MODULES,
			'moduleControllerDirectoryName' => "controllers",
			'defaultModule'		=> "default",
			'defaultAction'		=> "index",
			'defaultControllerName' => "index",
		),
		
		/* RESOURCES: Doctrine ORM Layer */
		'doctrine'			=> array(
            'autoGenerateProxies' => (DF_APPLICATION_ENV == "development"),
            'proxyNamespace'    => 'Proxy',
            'proxyPath'         => DF_INCLUDE_MODELS.'/Proxy',
            'modelPath'         => DF_INCLUDE_MODELS,
			'conn'				=> array(
                'driver'        => 'pdo_mysql',
                'host'          => 'localhost',
                'dbname'        => 'pvladmin_app',
                'user'          => 'pvladmin_app',
                'password'      => 'PvlWebDatabase2013!',
                'driverOptions' => array(
                	1002	=> 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
                ),
			),
		),
				
		/* RESOURCES: Menu */
		'menu'				=> array(
			'enabled'			=> true,
		),
		
		/* RESOURCES: Layout */
		'layout'			=> array(
			'layout'			=> 'default',
			'layoutPath'		=> DF_INCLUDE_APP.'/layouts',
            'commonTemplates'	=> DF_INCLUDE_BASE.'/common',
		),

		/* RESOURCES: Session */
		'session' => array(
			'use_only_cookies' => true,
			'remember_me_seconds' => $session_lifetime,
		),
	),
);

/**
 * Doctrine autoloading.
 */

$config['autoload']['Entity'] = $config['resources']['doctrine']['modelPath'];
$config['autoload']['Proxy'] = $config['resources']['doctrine']['modelPath'];

/**
 * Development mode changes.
 */

if (DF_APPLICATION_ENV != 'production')
{
	$config['phpSettings']['display_startup_errors'] = 1;
	$config['phpSettings']['display_errors'] = 1;
    $config['phpSettings']['error_reporting'] = E_ALL & ~E_STRICT & ~E_NOTICE;
    
	unset($config['base_url']);

	$config['resources']['doctrine']['conn'] = array(
		'driver'        => 'pdo_mysql',
        'host'          => 'localhost',
        'dbname'        => 'pvl',
        'user'          => 'pvl',
        'password'      => '7AchaZAz',
	);
}

return $config;
