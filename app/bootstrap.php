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
define("DF_INCLUDE_LIB_LOCAL", DF_INCLUDE_BASE.'/library_local');

define("DF_UPLOAD_FOLDER", DF_INCLUDE_STATIC);

define("DF_URL_STATIC", (DF_IS_SECURE ? 'https:' : 'http:').'//static.ponyvillelive.com');

// Self-reference to current script.
if (isset($_SERVER['REQUEST_URI']))
	define("DF_THIS_PAGE", reset(explode("?", $_SERVER['REQUEST_URI'])));
else
	define("DF_THIS_PAGE", '');

define("DF_TIME", time());

// Application environment.
define('DF_APPLICATION_ENV_PATH', DF_INCLUDE_BASE.DIRECTORY_SEPARATOR.'.env');

if (!defined('DF_APPLICATION_ENV'))
    define('DF_APPLICATION_ENV', ($env = @file_get_contents(DF_APPLICATION_ENV_PATH)) ? trim($env) : 'development');

// Set error reporting.
error_reporting(E_ALL & ~E_NOTICE);

// Set include path (as needed by CLI access.)
$include_path = array(DF_INCLUDE_LIB, get_include_path());

// Loop through modules to find configuration files or libraries.
$module_config_dirs = array();
$modules = scandir(DF_INCLUDE_MODULES);
foreach($modules as $module)
{
	if ($module != '.' && $module != '..')
	{
		$config_directory = DF_INCLUDE_MODULES.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'config';
		if (file_exists($config_directory))
		{
			$module_config_dirs[$module] = $config_directory;
		}
		
		$library_directory = DF_INCLUDE_MODULES.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'library';
		if (file_exists($library_directory))
		{
			$include_path[] = $library_directory;
		}
	}
}

// Set include paths.
set_include_path(implode(PATH_SEPARATOR, $include_path));

// Save configuration object.
require('DF/Config.php');
$config = new DF\Config(DF_INCLUDE_APP.'/config');
$config->preload(array('application','general'));

$module_config = array();
if ($module_config_dirs)
{
	foreach($module_config_dirs as $module_name => $config_dir)
	{
		$module_config[$module_name] = new \DF\Config($config_dir);
	}
}

require('DF/Loader.php');
DF\Loader::register($config->application->autoload);

// Initialize the ZendFramework Application Bootstrapper.
require('Zend/Application.php');
$application = new Zend_Application('application', $config->application);
$application->getBootstrap();
$application->bootstrap('doctrine');

// Save the configuration object to the global registry.
Zend_Registry::set('application', $application);
Zend_Registry::set('config', $config);
Zend_Registry::set('module_config', $module_config);
Zend_Registry::set('cache', new DF\Cache);
Zend_Registry::set('auth', new DF\Auth\Model);
Zend_Registry::set('acl', new DF\Acl\Instance);

// PVL-specific customization.
$system_tz = \PVL\Customization::get('timezone');
@date_default_timezone_set($system_tz);