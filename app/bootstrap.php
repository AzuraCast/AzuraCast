<?php
/**
 * Global bootstrap file.
 */

// Security settings
define("APP_IS_COMMAND_LINE", (PHP_SAPI == "cli"));
define("APP_IS_SECURE",
    (!APP_IS_COMMAND_LINE && (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")) ? true : false);

if (!defined('APP_TESTING_MODE')) {
    define('APP_TESTING_MODE', false);
}

// General includes
define("APP_INCLUDE_BASE", dirname(__FILE__));
define("APP_INCLUDE_ROOT", realpath(APP_INCLUDE_BASE . '/..'));
define("APP_INCLUDE_WEB", APP_INCLUDE_ROOT . '/web');
define("APP_INCLUDE_STATIC", APP_INCLUDE_WEB . '/static');

// Detect Docker containerization
define("APP_INSIDE_DOCKER", file_exists(APP_INCLUDE_ROOT.'/../.docker'));

define("APP_INCLUDE_VENDOR", APP_INCLUDE_ROOT . '/vendor');

define("APP_INCLUDE_TEMP", APP_INCLUDE_ROOT . '/../www_tmp');
define("APP_INCLUDE_CACHE", APP_INCLUDE_TEMP . '/cache');

define("APP_INCLUDE_MODULES", APP_INCLUDE_BASE.'/modules');

define("APP_UPLOAD_FOLDER", APP_INCLUDE_STATIC);

// Application environment.
define('APP_APPLICATION_ENV', $_SERVER['APP_APPLICATION_ENV']
    ?? @file_get_contents(APP_INCLUDE_BASE . '/.env')
    ?? $_SERVER['X-App-Dev-Environment']
    ?? 'development');

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $_SERVER['HTTPS'] = (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https');
}

// Composer autoload.
$autoloader = require(APP_INCLUDE_VENDOR . '/autoload.php');

// Set up DI container.
$app_settings = [
    'outputBuffering' => false,
    'displayErrorDetails' => true,
    'addContentLengthHeader' => false,
];

if (APP_APPLICATION_ENV !== 'development') {
    $app_settings['routerCacheFile'] = APP_INCLUDE_TEMP . '/app_routes.cache.php';
}

$di = new \Slim\Container(['settings' => $app_settings]);

// Save configuration object.
$config = new \App\Config(APP_INCLUDE_BASE . '/config', $di);

// Add application autoloaders to Composer's autoloader handler.
$autoload_classes = $config->application->autoload->toArray();
foreach ($autoload_classes['psr0'] as $class_key => $class_dir) {
    $autoloader->add($class_key, $class_dir);
}

foreach ($autoload_classes['psr4'] as $class_key => $class_dir) {
    $autoloader->addPsr4($class_key, $class_dir);
}

// Set URL constants from configuration.
$app_cfg = $config->application;
if ($app_cfg->base_url) {
    define('APP_BASE_URL', $app_cfg->base_url);
}

// Apply PHP settings.
$php_settings = $config->application->phpSettings->toArray();
foreach ($php_settings as $setting_key => $setting_value) {
    if (is_array($setting_value)) {
        foreach ($setting_value as $setting_subkey => $setting_subval) {
            ini_set($setting_key . '.' . $setting_subkey, $setting_subval);
        }
    } else {
        ini_set($setting_key, $setting_value);
    }
}

// Iterate through modules.
$modules = array_diff(scandir(APP_INCLUDE_MODULES), ['..', '.']);

foreach($modules as $module) {
    $full_path = APP_INCLUDE_MODULES.'/'.$module;

    $controller_prefix = 'Controller\\'.ucfirst($module).'\\';
    $autoloader->addPsr4($controller_prefix, $full_path.'/controllers');
}

$di['modules'] = $modules;

// Define services.
call_user_func(include(__DIR__.'/bootstrap/services.php'), $di, $config);

// Initialize cache.
$cache = $di->get('cache');

if (!APP_IS_COMMAND_LINE || APP_TESTING_MODE) {
    /** @var \AzuraCast\Customization $customization */
    $customization = $di->get('customization');

    // Set time zone.
    date_default_timezone_set($customization->getTimeZone());

    // Localization
    $locale = $customization->getLocale();
    putenv("LANG=" . $locale);
    setlocale(LC_ALL, $locale);

    $locale_domain = 'default';
    bindtextdomain($locale_domain, APP_INCLUDE_BASE . '/locale');
    bind_textdomain_codeset($locale_domain, 'UTF-8');
    textdomain($locale_domain);
}

return $di;