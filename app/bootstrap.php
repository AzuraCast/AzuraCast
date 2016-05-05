<?php
/**
 * Global bootstrap file.
 */

// Security settings
define("APP_IS_COMMAND_LINE", (PHP_SAPI == "cli"));
define("APP_IS_SECURE", (!APP_IS_COMMAND_LINE && (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")) ? TRUE : FALSE);

// General includes
define("APP_INCLUDE_BASE", dirname(__FILE__));
define("APP_INCLUDE_ROOT", realpath(APP_INCLUDE_BASE.'/..'));
define("APP_INCLUDE_WEB", APP_INCLUDE_ROOT.'/web');
define("APP_INCLUDE_STATIC", APP_INCLUDE_WEB.'/static');

define("APP_INCLUDE_MODELS", APP_INCLUDE_BASE.'/models');
define("APP_INCLUDE_MODULES", APP_INCLUDE_BASE.'/modules');

define("APP_INCLUDE_TEMP", APP_INCLUDE_ROOT.'/../www_tmp');
define("APP_INCLUDE_CACHE", APP_INCLUDE_TEMP.'/cache');

define("APP_INCLUDE_LIB", APP_INCLUDE_BASE.'/library');
define("APP_INCLUDE_VENDOR", APP_INCLUDE_ROOT.'/vendor');

define("APP_UPLOAD_FOLDER", APP_INCLUDE_STATIC);

// Application environment.
if (isset($_SERVER['APP_APPLICATION_ENV']))
    define('APP_APPLICATION_ENV', $_SERVER['APP_APPLICATION_ENV']);
elseif (file_exists(APP_INCLUDE_BASE.'/.env'))
    define('APP_APPLICATION_ENV', ($env = @file_get_contents(APP_INCLUDE_BASE.'/.env')) ? trim($env) : 'development');
elseif (isset($_SERVER['X-App-Dev-Environment']) && $_SERVER['X-App-Dev-Environment'])
    define('APP_APPLICATION_ENV', 'development');
else
    define('APP_APPLICATION_ENV', 'development');

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']))
    $_SERVER['HTTPS'] = (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https');

// Composer autoload.
$autoloader = require(APP_INCLUDE_VENDOR . DIRECTORY_SEPARATOR . 'autoload.php');

// Save configuration object.
require(APP_INCLUDE_LIB . '/App/Config.php');
require(APP_INCLUDE_LIB . '/App/Config/Item.php');

$config = new \App\Config(APP_INCLUDE_BASE.'/config');
$config->preload(array('application'));

// Set URL constants from configuration.
$app_cfg = $config->application;
if ($app_cfg->base_url)
    define('APP_BASE_URL', $app_cfg->base_url);

// Apply PHP settings.
$php_settings = $config->application->phpSettings->toArray();
foreach($php_settings as $setting_key => $setting_value)
{
    if (is_array($setting_value)) {
        foreach($setting_value as $setting_subkey => $setting_subval)
            ini_set($setting_key.'.'.$setting_subkey, $setting_subval);
    } else {
        ini_set($setting_key, $setting_value);
    }
}

// Loop through modules to find configuration files or libraries.
$module_config_dirs = array();
$modules = scandir(APP_INCLUDE_MODULES);

$module_config = array();
$phalcon_modules = array();

foreach($modules as $module)
{
    if ($module == '.' || $module == '..')
        continue;

    $config_directory = APP_INCLUDE_MODULES.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'config';
    if (file_exists($config_directory))
        $module_config[$module] = new \App\Config($config_directory);

    $phalcon_modules[$module] = ucfirst($module);
}

$autoload_classes = $config->application->autoload->toArray();
foreach($autoload_classes['psr0'] as $class_key => $class_dir)
    $autoloader->add($class_key, $class_dir);

foreach($autoload_classes['psr4'] as $class_key => $class_dir)
    $autoloader->addPsr4($class_key, $class_dir);

// Set up Dependency Injection
if (APP_IS_COMMAND_LINE)
    $di = new \Phalcon\DI\FactoryDefault\CLI;
else
    $di = new \Phalcon\DI\FactoryDefault;

// Configs
$di->setShared('config', $config);
$di->setShared('module_config', function() use ($module_config) { return $module_config; });
$di->setShared('phalcon_modules', function() use ($phalcon_modules) { return $phalcon_modules; });

// Router
if (APP_IS_COMMAND_LINE) {
    $router = new \Phalcon\CLI\Router;
    $di->setShared('router', $router);
} else {
    $di->setShared('router', function () use ($di) {
        $router = new \App\Phalcon\Router(false);
        $router->setUriSource(\App\Phalcon\Router::URI_SOURCE_SERVER_REQUEST_URI);

        $router->setDi($di);

        $router_config = $di->get('config')->routes->toArray();

        $router->setDefaultModule($router_config['default_module']);
        $router->setDefaultController($router_config['default_controller']);
        $router->setDefaultAction($router_config['default_action']);
        $router->removeExtraSlashes(true);

        foreach ((array)$router_config['custom_routes'] as $route_path => $route_params)
        {
            $route = $router->add($route_path, $route_params);

            if (isset($route_params['name']))
                $route->setName($route_params['name']);
        }

        return $router;
    });
}

// Database
$di->setShared('em', function() use ($config) {
    try    {
        $db_conf = $config->application->resources->doctrine->toArray();
        $db_conf['conn'] = $config->db->toArray();

        $em = \App\Phalcon\Service\Doctrine::init($db_conf);
        return $em;
    }
    catch(\Exception $e)
    {
        throw new \App\Exception\Bootstrap($e->getMessage());
    }
});

$di->setShared('db', function() use ($config) {
    try
    {
        $db_conf = $config->application->resources->doctrine->toArray();
        $db_conf['conn'] = $config->db->toArray();

        $config = new \Doctrine\DBAL\Configuration;
        return \Doctrine\DBAL\DriverManager::getConnection($db_conf['conn'], $config);
    }
    catch(\Exception $e)
    {
        throw new \App\Exception\Bootstrap($e->getMessage());
    }
});

// Auth and ACL
$di->setShared('auth', array(
    'className' => '\App\Auth',
    'arguments' => array(
        array('type' => 'service', 'name' => 'session'),
    )
));

$di->setShared('acl', array(
    'className' => '\App\Acl',
    'arguments' => array(
        array('type' => 'service', 'name' => 'em'),
        array('type' => 'service', 'name' => 'auth'),
    )
));

// Caching
$di->setShared('cache_driver', function() use ($config) {
    $cache_config = $config->cache->toArray();

    switch($cache_config['cache'])
    {
        case 'redis':
            $cache_driver = new \Stash\Driver\Redis;
            $cache_driver->setOptions($cache_config['redis']);
            break;

        case 'memcached':
            $cache_driver = new \Stash\Driver\Memcache;
            $cache_driver->setOptions($cache_config['memcached']);
            break;

        case 'file':
            $cache_driver = new \Stash\Driver\FileSystem;
            $cache_driver->setOptions($cache_config['file']);
            break;

        default:
        case 'memory':
        case 'ephemeral':
            $cache_driver = new \Stash\Driver\Ephemeral;
            break;
    }

    // Register Stash as session handler if necessary.
    if (!($cache_driver instanceof \Stash\Driver\Ephemeral))
    {
        $pool = new \Stash\Pool($cache_driver);
        $pool->setNamespace(\App\Cache::getSitePrefix('session'));

        $session = new \Stash\Session($pool);
        \Stash\Session::registerHandler($session);
    }

    return $cache_driver;
});

$di->set('cache', array(
    'className' => '\App\Cache',
    'arguments' => array(
        array('type' => 'service', 'name' => 'cache_driver'),
        array('type' => 'parameter', 'value' => 'user'),
    )
));

// Register URL handler.
$di->setShared('url', array(
    'className' => '\App\Url',
    'arguments' => array(
        array('type' => 'service', 'name' => 'config'),
        array('type' => 'service', 'name' => 'request'),
        array('type' => 'service', 'name' => 'dispatcher'),
    )
));

// Register session service.
$di->setShared('session', '\App\Session');

// Register CSRF prevention security token service.
$di->setShared('csrf', array(
    'className' => '\App\Csrf',
    'arguments' => array(
        array('type' => 'service', 'name' => 'session'),
    )
));

// Register view helpers.
$di->setShared('viewHelper', '\App\Phalcon\Service\ViewHelper');

// Register Flash notification service.
$di->setShared('flash', array(
    'className' => '\App\Flash',
    'arguments' => array(
        array('type' => 'service', 'name' => 'session'),
    )
));

// Register cryptography helper.
$di->setShared('crypto', array(
    'className' => '\App\Crypto',
    'arguments' => array(
        array('type' => 'service', 'name' => 'config'),
    )
));

$di->setShared('user', function() use ($di) {
    $auth = $di['auth'];

    if ($auth->isLoggedIn())
        return $auth->getLoggedInUser();
    else
        return NULL;
});

// Initialize cache.
$cache = $di->get('cache');