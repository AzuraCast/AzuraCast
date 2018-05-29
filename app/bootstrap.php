<?php
/**
 * Global bootstrap file.
 */

// Security settings
define('APP_IS_COMMAND_LINE', PHP_SAPI === 'cli');
define('APP_IS_SECURE', !APP_IS_COMMAND_LINE && (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === "on"));

if (!defined('APP_TESTING_MODE')) {
    define('APP_TESTING_MODE', false);
}

// General includes
define('APP_INCLUDE_BASE', __DIR__);
define('APP_INCLUDE_ROOT', dirname(APP_INCLUDE_BASE));
define('APP_INCLUDE_WEB', APP_INCLUDE_ROOT . '/web');
define('APP_INCLUDE_STATIC', APP_INCLUDE_WEB . '/static');

// Detect Docker containerization
define('APP_INSIDE_DOCKER', file_exists(APP_INCLUDE_ROOT.'/../.docker'));

define('APP_INCLUDE_VENDOR', APP_INCLUDE_ROOT . '/vendor');

define('APP_INCLUDE_TEMP', dirname(APP_INCLUDE_ROOT) . '/www_tmp');
define('APP_INCLUDE_CACHE', APP_INCLUDE_TEMP . '/cache');

// Set up application environment.
if (APP_INSIDE_DOCKER) {
    $_ENV = getenv();
} else if (file_exists(APP_INCLUDE_BASE.'/env.ini')) {
    $_ENV = array_merge($_ENV, parse_ini_file(APP_INCLUDE_BASE.'/env.ini'));
}

// Application environment.
define('APP_APPLICATION_ENV', $_ENV['application_env'] ?? $_ENV['APPLICATION_ENV'] ?? 'production');
define('APP_IN_PRODUCTION', APP_APPLICATION_ENV === 'production');

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $_SERVER['HTTPS'] = (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
}

// Apply PHP settings.
ini_set('display_startup_errors',       !APP_IN_PRODUCTION ? 1 : 0);
ini_set('display_errors',               !APP_IN_PRODUCTION ? 1 : 0);
ini_set('log_errors',                   1);
ini_set('error_log',                    (APP_INSIDE_DOCKER) ? '/dev/stderr' : APP_INCLUDE_TEMP.'/php_errors.log');
ini_set('error_reporting',              E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT);
ini_set('session.use_only_cookies',     1);
ini_set('session.cookie_httponly',      1);
ini_set('session.cookie_lifetime',      86400);
ini_set('session.use_strict_mode',      1);

// Composer autoload.
$autoloader = require(APP_INCLUDE_VENDOR . '/autoload.php');
$autoloader->addPsr4('\\Proxy\\', APP_INCLUDE_TEMP . '/proxies');

// Set up DI container.
$di = new \Slim\Container([
    'settings' => [
        'outputBuffering' => false,
        'displayErrorDetails' => !APP_IN_PRODUCTION,
        'addContentLengthHeader' => false,
        'routerCacheFile' => (APP_IN_PRODUCTION) ? APP_INCLUDE_TEMP . '/app_routes.cache.php' : false,
        // 'determineRouteBeforeAppMiddleware' => true,
    ]
]);

// Define services.
$settings = require(__DIR__.'/bootstrap/settings.php');
call_user_func(include(__DIR__.'/bootstrap/services.php'), $di, $settings);

return $di;