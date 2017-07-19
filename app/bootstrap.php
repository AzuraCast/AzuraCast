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

// Set up application environment.
if (file_exists(APP_INCLUDE_BASE.'/env.ini')) {
    $_ENV = array_merge($_ENV, parse_ini_file(APP_INCLUDE_BASE.'/env.ini'));
}

// Application environment.
define('APP_APPLICATION_ENV', $_ENV['application_env'] ?? 'development');
define('APP_IN_PRODUCTION', APP_APPLICATION_ENV === 'production');

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $_SERVER['HTTPS'] = (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https');
}

// Apply PHP settings.
ini_set('display_startup_errors',       !APP_IN_PRODUCTION ? 1 : 0);
ini_set('display_errors',               !APP_IN_PRODUCTION ? 1 : 0);
ini_set('log_errors',                   1);
ini_set('error_log',                    APP_INCLUDE_TEMP . '/php_errors.log');
ini_set('error_reporting',              E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT);
ini_set('session.use_only_cookies',     1);
ini_set('session.cookie_lifetime',      86400);

// Composer autoload.
$autoloader = require(APP_INCLUDE_VENDOR . '/autoload.php');
$autoloader->addPsr4('\\Proxy\\', APP_INCLUDE_TEMP . '/proxies');

// Set up DI container.
$di = new \Slim\Container([
    'outputBuffering' => false,
    'displayErrorDetails' => true,
    'addContentLengthHeader' => false,
    'routerCacheFile' => (APP_IN_PRODUCTION) ? APP_INCLUDE_TEMP . '/app_routes.cache.php' : null,
]);

// Iterate through modules.
$modules = array_diff(scandir(APP_INCLUDE_MODULES), ['..', '.']);

foreach($modules as $module) {
    $full_path = APP_INCLUDE_MODULES.'/'.$module;

    $controller_prefix = 'Controller\\'.ucfirst($module).'\\';
    $autoloader->addPsr4($controller_prefix, $full_path.'/controllers');
}

$di['modules'] = $modules;

// Define services.
$settings = require(__DIR__.'/bootstrap/settings.php');
call_user_func(include(__DIR__.'/bootstrap/services.php'), $di, $settings);

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