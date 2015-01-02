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
define("DF_INCLUDE_MODULES", DF_INCLUDE_ROOT.'/modules');
define("DF_INCLUDE_MODELS", DF_INCLUDE_BASE.'/models');
define("DF_INCLUDE_STATIC", DF_INCLUDE_WEB.'/static');
define("DF_INCLUDE_TEMP", DF_INCLUDE_ROOT.'/../www_tmp');
define("DF_INCLUDE_CACHE", DF_INCLUDE_TEMP.'/cache');

define("DF_INCLUDE_LIB", DF_INCLUDE_ROOT.'/library');
define("DF_INCLUDE_VENDOR", DF_INCLUDE_ROOT.'/../vendor');

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

// Set error reporting for the bootstrapping process.
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

// Set include paths.
set_include_path(implode(PATH_SEPARATOR, $include_path));

// Composer autoload.
$autoloader = require(DF_INCLUDE_VENDOR.DIRECTORY_SEPARATOR.'autoload.php');

// Save configuration object.
require(DF_INCLUDE_LIB.'/DF/Config.php');

$config = new \DF\Config(DF_INCLUDE_ROOT.'/config');
$config->preload(array('application','general'));

// Loop through modules to find configuration files or libraries.
$module_config_dirs = array();
$modules = scandir(DF_INCLUDE_MODULES);

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
    );
}

$autoload_classes = $config->application->autoload->toArray();
foreach($autoload_classes['psr0'] as $class_key => $class_dir)
    $autoloader->add($class_key, $class_dir);

foreach($autoload_classes['psr4'] as $class_key => $class_dir)
    $autoloader->addPsr4($class_key, $class_dir);

// Initialize the ZendFramework Application Bootstrapper.
// $application = new \Zend_Application('application', $config->application);
// $application->getBootstrap();

// Bootstrap the DB for the following options.
// TODO
//$application->bootstrap('doctrine');
// $em = \Zend_Registry::get('em');
// \DF\Doctrine\Session\SaveHandler::register($em);

// Register error-handler.
$run     = new \Whoops\Run;

$handler = new \Whoops\Handler\PrettyPageHandler;
$handler->setPageTitle("Whoops! There was a problem.");
$run->pushHandler($handler);

// $run->pushHandler(new \Whoops\Handler\JsonResponseHandler);

$run->register();

$di = new \Phalcon\DI\FactoryDefault();

$di['router'] = function() {
    $router = require DF_INCLUDE_BASE.'/routes.php';
    return $router;
};

// Configs
$di['config'] = $config;
$di['module_config'] = $module_config;

// Database
$di->setShared('em', '\DF\Doctrine\Service');

// Auth and ACL
$di->setShared('auth', '\DF\Auth\Model');
$di->setShared('acl', '\DF\Acl\Instance');
$di->setShared('cache', '\DF\Cache');

// Register URL handler.
$di->set('url', function() {
    $url = new \Phalcon\Mvc\Url();
    return $url;
});

// Register session.
$di->set('session', function() {
    $session = new \Phalcon\Session\Adapter\Files();
    $session->start();
    return $session;
});

// PVL-specific customization.
$system_tz = \PVL\Customization::get('timezone');
@date_default_timezone_set($system_tz);