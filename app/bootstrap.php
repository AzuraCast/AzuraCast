<?php
/**
 * Global bootstrap file.
 */

// Security settings
define('DF_IS_COMMAND_LINE', (PHP_SAPI == "cli"));
define("DF_IS_SECURE", (!DF_IS_COMMAND_LINE && (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")) ? TRUE : FALSE);

// General includes
define("DF_INCLUDE_BASE", dirname(__FILE__));
define("DF_INCLUDE_ROOT", realpath(DF_INCLUDE_BASE.'/..'));
define("DF_INCLUDE_WEB", DF_INCLUDE_ROOT.'/web');

define("DF_INCLUDE_APP", DF_INCLUDE_BASE);
define("DF_INCLUDE_MODULES", DF_INCLUDE_BASE.'/modules');
define("DF_INCLUDE_MODELS", DF_INCLUDE_BASE.'/models');
define("DF_INCLUDE_STATIC", DF_INCLUDE_WEB.'/static');
define("DF_INCLUDE_TEMP", DF_INCLUDE_ROOT.'/../www_tmp');
define("DF_INCLUDE_CACHE", DF_INCLUDE_TEMP.'/cache');

define("DF_INCLUDE_LIB", DF_INCLUDE_BASE.'/library');
define("DF_INCLUDE_VENDOR", DF_INCLUDE_ROOT.'/vendor');

define("DF_UPLOAD_FOLDER", DF_INCLUDE_STATIC);

// Self-reference to current script.
if (isset($_SERVER['REQUEST_URI']))
    define("DF_THIS_PAGE", reset(explode("?", $_SERVER['REQUEST_URI'])));
else
    define("DF_THIS_PAGE", '');

// Application environment.
define('DF_APPLICATION_ENV_PATH', DF_INCLUDE_BASE.DIRECTORY_SEPARATOR.'.env');

if (!defined('DF_APPLICATION_ENV'))
    define('DF_APPLICATION_ENV', ($env = @file_get_contents(DF_APPLICATION_ENV_PATH)) ? trim($env) : 'development');

// Composer autoload.
$autoloader = require(DF_INCLUDE_VENDOR . DIRECTORY_SEPARATOR . 'autoload.php');

// Save configuration object.
require(DF_INCLUDE_LIB . '/DF/Config.php');

$config = new \DF\Config(DF_INCLUDE_BASE.'/config');
$config->preload(array('application','general'));

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
$modules = scandir(DF_INCLUDE_MODULES);

$module_config = array();
$phalcon_modules = array();

foreach($modules as $module)
{
    if ($module == '.' || $module == '..')
        continue;

    $config_directory = DF_INCLUDE_MODULES.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'config';
    if (file_exists($config_directory))
        $module_config[$module] = new \DF\Config($config_directory);

    $phalcon_modules[$module] = array(
        'className' => 'Modules\\'.ucfirst($module).'\Module',
        'path' => DF_INCLUDE_MODULES.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'/Module.php',

        'controllerClass' => 'Modules\\'.ucfirst($module).'\Controllers',
        'controllerPath' => DF_INCLUDE_MODULES.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'/controllers',
    );
}

$autoload_classes = $config->application->autoload->toArray();
foreach($autoload_classes['psr0'] as $class_key => $class_dir)
    $autoloader->add($class_key, $class_dir);

foreach($autoload_classes['psr4'] as $class_key => $class_dir)
    $autoloader->addPsr4($class_key, $class_dir);

// Set up Dependency Injection
$di = new \Phalcon\DI\FactoryDefault();

// Configs
$di->setShared('config', $config);
$di->setShared('module_config', function() use ($module_config) { return $module_config; });
$di->setShared('phalcon_modules', function() use ($phalcon_modules) { return $phalcon_modules; });

// Router
$di->setShared('router', function() use ($di) {
    $router = new \DF\Phalcon\Router(false);
    $router->setUriSource(\DF\Phalcon\Router::URI_SOURCE_SERVER_REQUEST_URI);

    $router->setDi($di);

    $router_config = $di->get('config')->routes->toArray();

    $router->setDefaultModule($router_config['default_module']);
    $router->setDefaultController($router_config['default_controller']);
    $router->setDefaultAction($router_config['default_action']);
    $router->removeExtraSlashes(true);

    foreach((array)$router_config['custom_routes'] as $route_path => $route_params)
    {
        $route = $router->add($route_path, $route_params);

        if (isset($route_params['name']))
            $route->setName($route_params['name']);
    }

    return $router;
});

// Database
$di->setShared('em', function() use ($config) {
    $db_conf = $config->application->resources->doctrine->toArray();
    $db_conf['conn'] = $config->db->toArray();

    $em = \DF\Phalcon\Service\Doctrine::init($db_conf);
    return $em;
});

// Auth and ACL
$di->setShared('auth', '\DF\Auth\Model');
$di->setShared('acl', '\DF\Acl\Instance');
$di->setShared('cache', '\DF\Cache');

// Register URL handler.
$di->set('url', function() use ($config) {
    $url = new \Phalcon\Mvc\Url();

    $url->setBaseUri('/');
    $url->setStaticBaseUri('/static/');

    return $url;
});

// Register session.
$di->set('session', function() {
    $session = new \Phalcon\Session\Adapter\Files();
    $session->start();
    return $session;
});

// Register view helpers.
$di->setShared('viewHelper', '\DF\Phalcon\Service\ViewHelper');

// PVL-specific customization.
$system_tz = \PVL\Customization::get('timezone');
@date_default_timezone_set($system_tz);