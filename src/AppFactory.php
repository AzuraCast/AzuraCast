<?php

namespace App;

use App\Http\Factory\ServerRequestFactory;
use DI;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Invoker;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;

class AppFactory
{
    public static function create($autoloader = null, $appSettings = [], $diDefinitions = []): App
    {
        // Register Annotation autoloader
        if (null !== $autoloader) {
            AnnotationRegistry::registerLoader([$autoloader, 'loadClass']);
        }

        $settings = new Settings(self::buildSettings($appSettings));
        Settings::setInstance($settings);

        self::applyPhpSettings($settings);

        // Helper constants for annotations.
        define('SAMPLE_TIMESTAMP', random_int(time() - 86400, time() + 86400));

        // Override DI definitions for settings.
        $diDefinitions[Settings::class] = $settings;
        $diDefinitions['settings'] = DI\Get(Settings::class);

        if ($autoloader) {
            $plugins = new Plugins($settings[Settings::BASE_DIR] . '/plugins');

            $diDefinitions[Plugins::class] = $plugins;
            $diDefinitions = $plugins->registerServices($diDefinitions);
        } else {
            $plugins = null;
        }

        $di = self::buildContainer($settings, $diDefinitions);

        Logger::setInstance($di->get(LoggerInterface::class));

        ServerRequestCreatorFactory::setSlimHttpDecoratorsAutomaticDetection(false);
        ServerRequestCreatorFactory::setServerRequestCreator(new ServerRequestFactory());

        $app = self::createFromContainer($di);
        $di->set(App::class, $app);

        self::updateRouteHandling($app);
        self::buildRoutes($app);

        return $app;
    }

    /**
     * @return mixed[]
     */
    protected static function buildSettings(array $settings): array
    {
        if (!isset($settings[Settings::BASE_DIR])) {
            throw new Exception\BootstrapException('No base directory specified!');
        }

        $settings[Settings::TEMP_DIR] = dirname($settings[Settings::BASE_DIR]) . '/www_tmp';

        $settings[Settings::IS_DOCKER] = file_exists(dirname($settings[Settings::BASE_DIR]) . '/.docker');
        $settings[Settings::DOCKER_REVISION] = getenv('AZURACAST_DC_REVISION') ?? 1;

        $settings[Settings::CONFIG_DIR] = $settings[Settings::BASE_DIR] . '/config';
        $settings[Settings::VIEWS_DIR] = $settings[Settings::BASE_DIR] . '/templates';

        if (!isset($settings[Settings::BASE_DIR])) {
            throw new Exception\BootstrapException('No base directory specified!');
        }

        if (!isset($settings[Settings::TEMP_DIR])) {
            $settings[Settings::TEMP_DIR] = dirname($settings[Settings::BASE_DIR]) . '/www_tmp';
        }

        if (!isset($settings[Settings::CONFIG_DIR])) {
            $settings[Settings::CONFIG_DIR] = $settings[Settings::BASE_DIR] . '/config';
        }

        if (!isset($settings[Settings::VIEWS_DIR])) {
            $settings[Settings::VIEWS_DIR] = $settings[Settings::BASE_DIR] . '/templates';
        }

        if ($settings[Settings::IS_DOCKER]) {
            $_ENV = getenv();
        } elseif (file_exists($settings[Settings::BASE_DIR] . '/env.ini')) {
            $_ENV = array_merge($_ENV, parse_ini_file($settings[Settings::BASE_DIR] . '/env.ini'));
        }

        if (!isset($settings[Settings::APP_ENV])) {
            $settings[Settings::APP_ENV] = $_ENV['APPLICATION_ENV'] ?? Settings::ENV_PRODUCTION;
        }

        if (isset($_ENV['BASE_URL'])) {
            $settings[Settings::BASE_URL] = $_ENV['BASE_URL'];
        }

        if (isset($_ENV['ENABLE_ADVANCED_FEATURES'])) {
            $settings[Settings::ENABLE_ADVANCED_FEATURES] = $_ENV['ENABLE_ADVANCED_FEATURES'];
        }

        if (file_exists($settings[Settings::CONFIG_DIR] . '/settings.php')) {
            $settingsFile = require($settings[Settings::CONFIG_DIR] . '/settings.php');

            if (is_array($settingsFile)) {
                $settings = array_merge($settings, $settingsFile);
            }
        }

        return $settings;
    }

    protected static function applyPhpSettings(Settings $settings): void
    {
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT);

        ini_set('display_startup_errors', !$settings->isProduction() ? '1' : '0');
        ini_set('display_errors', !$settings->isProduction() ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set(
            'error_log',
            $settings[Settings::IS_DOCKER]
                ? '/dev/stderr'
                : $settings[Settings::TEMP_DIR] . '/php_errors.log'
        );
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_lifetime', '86400');
        ini_set('session.use_strict_mode', '1');

        date_default_timezone_set('UTC');

        session_cache_limiter('');
    }

    protected static function buildContainer(Settings $settings, array $diDefinitions = []): DI\Container
    {
        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->useAnnotations(true);
        $containerBuilder->useAutowiring(true);

        if ($settings->isProduction()) {
            $containerBuilder->enableCompilation($settings[Settings::TEMP_DIR]);
        }

        if (!isset($diDefinitions[Settings::class])) {
            $diDefinitions[Settings::class] = $settings;
            $diDefinitions['settings'] = DI\Get(Settings::class);
        }

        $containerBuilder->addDefinitions($diDefinitions);

        // Check for services.php file and include it if one exists.
        $config_dir = $settings[Settings::CONFIG_DIR];
        if (file_exists($config_dir . '/services.php')) {
            $containerBuilder->addDefinitions($config_dir . '/services.php');
        }

        return $containerBuilder->build();
    }

    protected static function createFromContainer(ContainerInterface $container): App
    {
        $responseFactory = $container->has(ResponseFactoryInterface::class)
            ? $container->get(ResponseFactoryInterface::class)
            : new Http\Factory\ResponseFactory();

        $callableResolver = $container->has(CallableResolverInterface::class)
            ? $container->get(CallableResolverInterface::class)
            : null;

        $routeCollector = $container->has(RouteCollectorInterface::class)
            ? $container->get(RouteCollectorInterface::class)
            : null;

        $routeResolver = $container->has(RouteResolverInterface::class)
            ? $container->get(RouteResolverInterface::class)
            : null;

        $middlewareDispatcher = $container->has(MiddlewareDispatcherInterface::class)
            ? $container->get(MiddlewareDispatcherInterface::class)
            : null;

        return new App(
            $responseFactory,
            $container,
            $callableResolver,
            $routeCollector,
            $routeResolver,
            $middlewareDispatcher
        );
    }

    /**
     * @param App $app
     */
    protected static function updateRouteHandling(App $app): void
    {
        $di = $app->getContainer();
        $routeCollector = $app->getRouteCollector();

        /** @var Settings $settings */
        $settings = $di->get(Settings::class);

        // Use the PHP-DI Bridge's action invocation helper.
        $resolvers = [
            // Inject parameters by name first
            new Invoker\ParameterResolver\AssociativeArrayResolver(),
            // Then inject services by type-hints for those that weren't resolved
            new Invoker\ParameterResolver\Container\TypeHintContainerResolver($di),
            // Then fall back on parameters default values for optional route parameters
            new Invoker\ParameterResolver\DefaultValueResolver(),
        ];

        $invoker = new Invoker\Invoker(new Invoker\ParameterResolver\ResolverChain($resolvers), $di);
        $controllerInvoker = new DI\Bridge\Slim\ControllerInvoker($invoker);

        $routeCollector->setDefaultInvocationStrategy($controllerInvoker);

        if ($settings->isProduction()) {
            $routeCollector->setCacheFile($settings[Settings::TEMP_DIR] . '/app_routes.cache.php');
        }
    }

    /**
     * @param App $app
     */
    protected static function buildRoutes(App $app): void
    {
        $di = $app->getContainer();

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $di->get(EventDispatcher::class);
        $dispatcher->dispatch(new Event\BuildRoutes($app));
    }
}
