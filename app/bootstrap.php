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
$autoloader = require(APP_INCLUDE_VENDOR . '/autoload.php');
$autoloader->add('App', APP_INCLUDE_LIB);

// Set up DI container.
$app_settings = [
    'displayErrorDetails' => true,
    'addContentLengthHeader' => false,
];

if (APP_APPLICATION_ENV !== 'development')
    $app_settings['routerCacheFile'] = APP_INCLUDE_TEMP.'/app_routes.cache.php';

$di = new \Slim\Container(['settings' => $app_settings]);

// Save configuration object.
$config = new \App\Config(APP_INCLUDE_BASE.'/config', $di);

// Add application autoloaders to Composer's autoloader handler.
$autoload_classes = $config->application->autoload->toArray();
foreach($autoload_classes['psr0'] as $class_key => $class_dir)
    $autoloader->add($class_key, $class_dir);

foreach($autoload_classes['psr4'] as $class_key => $class_dir)
    $autoloader->addPsr4($class_key, $class_dir);

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

// Override Slim handlers.
$di['callableResolver'] = function($di) {
    return new \App\Mvc\Resolver($di);
};

$di['errorHandler'] = function($di) {
    return function ($request, $response, $exception) use ($di) {
        return \App\Mvc\ErrorHandler::handle($di, $request, $response, $exception);
    };
};

$di['notFoundHandler'] = function ($di) {
    return function ($request, $response) use ($di) {
        $view = $di['view'];
        $template = $view->render('system/error_pagenotfound');

        $response->getBody()->write($template);
        return $response;
    };
};

// Loop through modules to find configuration files.
$modules = array_diff(scandir(APP_INCLUDE_MODULES), ['..', '.']);
$module_config = array();

foreach($modules as $module)
{
    $full_path = APP_INCLUDE_MODULES.'/'.$module;

    $config_directory = $full_path.'/config';
    if (file_exists($config_directory))
        $module_config[$module] = new \App\Config($config_directory, $di);

    $module_class = 'Modules\\'.ucfirst($module).'\\Controllers\\';
    $autoloader->addPsr4($module_class, $full_path.'/controllers');
}

// Configs
$di['config'] = $config;
$di['module_config'] = $module_config;

// Database
$di['em'] = function($di) {
    try
    {
        $config = $di['config'];
        $db_conf = $config->application->doctrine->toArray();
        $db_conf['conn'] = $config->db->toArray();

        return \App\Doctrine\EntityManagerFactory::create($di, $db_conf);
    }
    catch(\Exception $e)
    {
        throw new \App\Exception\Bootstrap($e->getMessage());
    }
};

$di['db'] = function($di) {
    try
    {
        $config = $di['config'];

        $db_conf = $config->application->doctrine->toArray();
        $db_conf['conn'] = $config->db->toArray();

        $config = new \Doctrine\DBAL\Configuration;
        return \Doctrine\DBAL\DriverManager::getConnection($db_conf['conn'], $config);
    }
    catch(\Exception $e)
    {
        throw new \App\Exception\Bootstrap($e->getMessage());
    }
};

// Auth and ACL
$di['auth'] = function($di) {
    return new \App\Auth($di['session'], $di['em']->getRepository('Entity\User'));
};

$di['acl'] = function($di) {
    return new \App\Acl\StationAcl($di['em'], $di['auth']);
};

// Caching
$di['cache_driver'] = function($di) {
    $config = $di['config'];
    $cache_config = $config->cache->toArray();

    switch($cache_config['cache'])
    {
        case 'redis':
            $cache_driver = new \Stash\Driver\Redis($cache_config['redis']);
            break;

        case 'memcached':
            $cache_driver = new \Stash\Driver\Memcache($cache_config['memcached']);
            break;

        case 'file':
            $cache_driver = new \Stash\Driver\FileSystem($cache_config['file']);
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
};

$di['cache'] = function($di) {
    return new \App\Cache($di['cache_driver'], 'user');
};

// Register URL handler.
$di['url'] = function($di) {
    return new \App\Url($di);
};

// Register session service.
$di['session'] = function($di) {
    return new \App\Session;
};

// Register CSRF prevention security token service.
$di['csrf'] = function($di) {
    return new \App\Csrf($di['session']);
};

// Register Flash notification service.
$di['flash'] = function($di) {
    return new \App\Flash($di['session']);
};

// InfluxDB
$di['influx'] = function($di) {
    $config = $di['config'];
    $opts = $config->influx->toArray();

    $influx = new \InfluxDB\Client($opts['host'], $opts['port']);
    return $influx->selectDB('stations');
};

// E-mail Messenger
$di['messenger'] = function($di) {
    return new \App\Messenger($di);
};

// Scheduled synchronization manager
$di['sync'] = function($di) {
    return new \App\Sync($di);
};

// Currently logged in user
$di['user'] = function($di) {
    $auth = $di['auth'];

    if ($auth->isLoggedIn())
        return $auth->getLoggedInUser();
    else
        return NULL;
};

$di['view'] = function($di) {
    $view = new \App\Mvc\View(APP_INCLUDE_BASE.'/templates');
    $view->setFileExtension('phtml');

    $view->addAppCommands($di);

    $view->addData([
        'di' => $di,
        'acl' => $di['acl'],
        'url' => $di['url'],
        'config' => $di['config'],
        'flash' => $di['flash'],
    ]);

    return $view;
};

// Initialize cache.
$cache = $di->get('cache');

if (!APP_IS_COMMAND_LINE)
{
    // Set time zone.
    $timezone = $di['em']->getRepository('Entity\Settings')->getSetting('timezone', date_default_timezone_get());
    date_default_timezone_set($timezone);

    /*
     * Commands:
     * find /var/azuracast/www -type f \( -name '*.php' -or -name '*.phtml' \) -print > list
     * xgettext --files-from=list --language=PHP -o /var/azuracast/www/app/locale/default.pot
     *
     * find /var/azuracast/www/app/locale -name \*.po -execdir msgfmt default.po -o default.mo \;
     */

    // Set up localization.
    $browser_locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);

    $supported_locales = $config->application->locale->supported->toArray();
    foreach($supported_locales as $lang_code => $lang_name)
    {
        if (strcmp(substr($browser_locale, 0, 2), substr($lang_code, 0, 2)) == 0)
        {
            $locale = $lang_code;
            break;
        }
    }

    if (empty($locale))
        $locale = $di['em']->getRepository('Entity\Settings')->getSetting('locale', $config->application->locale->default);

    putenv("LANG=".$locale);
    setlocale(LC_ALL, $locale);

    $locale_domain = 'default';
    bindtextdomain($locale_domain, APP_INCLUDE_BASE.'/locale');
    bind_textdomain_codeset($locale_domain, 'UTF-8');
    textdomain($locale_domain);
}

// Set up application and routing.
$app = new \Slim\App($di);

// Remove trailing slash from all URLs when routing.
$app->add(function (\Psr\Http\Message\RequestInterface $request, \Psr\Http\Message\ResponseInterface $response, callable $next) {
    $uri = $request->getUri();
    $path = $uri->getPath();

    if ($path != '/' && substr($path, -1) == '/')
    {
        // permanently redirect paths with a trailing slash
        // to their non-trailing counterpart
        $uri = $uri->withPath(substr($path, 0, -1));
        return $response->withRedirect((string)$uri, 301);
    }

    return $next($request, $response);
});

// Loop through modules to configure routes.
foreach($modules as $module)
{
    $routes_file = APP_INCLUDE_MODULES.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'routes.php';

    if (file_exists($routes_file))
        include($routes_file);
}