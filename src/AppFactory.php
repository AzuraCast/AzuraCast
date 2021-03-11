<?php

namespace App;

use App\Console\Application;
use App\Http\Factory\ResponseFactory;
use App\Http\Factory\ServerRequestFactory;
use DI;
use DI\Bridge\Slim\ControllerInvoker;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Invoker\Invoker;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Monolog\Registry;
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
    public static function createApp($autoloader = null, $appEnvironment = [], $diDefinitions = []): App
    {
        $di = self::buildContainer($autoloader, $appEnvironment, $diDefinitions);
        return self::buildAppFromContainer($di);
    }

    public static function createCli($autoloader = null, $appEnvironment = [], $diDefinitions = []): Application
    {
        $di = self::buildContainer($autoloader, $appEnvironment, $diDefinitions);
        self::buildAppFromContainer($di);

        $locale = $di->make(Locale::class);
        $locale->register();

        return $di->get(Application::class);
    }

    public static function buildAppFromContainer(DI\Container $container): App
    {
        ServerRequestCreatorFactory::setSlimHttpDecoratorsAutomaticDetection(false);
        ServerRequestCreatorFactory::setServerRequestCreator(new ServerRequestFactory());

        $responseFactory = $container->has(ResponseFactoryInterface::class)
            ? $container->get(ResponseFactoryInterface::class)
            : new ResponseFactory();

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

        $app = new App(
            $responseFactory,
            $container,
            $callableResolver,
            $routeCollector,
            $routeResolver,
            $middlewareDispatcher
        );
        $container->set(App::class, $app);

        $routeCollector = $app->getRouteCollector();

        // Use the PHP-DI Bridge's action invocation helper.
        $resolvers = [
            // Inject parameters by name first
            new AssociativeArrayResolver(),
            // Then inject services by type-hints for those that weren't resolved
            new TypeHintContainerResolver($container),
            // Then fall back on parameters default values for optional route parameters
            new DefaultValueResolver(),
        ];

        $invoker = new Invoker(new ResolverChain($resolvers), $container);
        $controllerInvoker = new ControllerInvoker($invoker);

        $routeCollector->setDefaultInvocationStrategy($controllerInvoker);

        $environment = $container->get(Environment::class);
        if ($environment->isProduction()) {
            $routeCollector->setCacheFile($environment->getTempDirectory() . '/app_routes.cache.php');
        }

        $eventDispatcher = $container->get(EventDispatcher::class);
        $eventDispatcher->dispatch(new Event\BuildRoutes($app));

        return $app;
    }

    public static function buildContainer(
        $autoloader = null,
        $appEnvironment = [],
        $diDefinitions = []
    ): DI\Container {
        // Register Annotation autoloader
        if (null !== $autoloader) {
            AnnotationRegistry::registerLoader([$autoloader, 'loadClass']);
        }

        $environment = self::buildEnvironment($appEnvironment);
        Environment::setInstance($environment);

        self::applyPhpSettings($environment);

        // Helper constants for annotations.
        define('SAMPLE_TIMESTAMP', random_int(time() - 86400, time() + 86400));

        // Override DI definitions for settings.
        $diDefinitions[Environment::class] = $environment;

        if ($autoloader) {
            $plugins = new Plugins($environment->getBaseDirectory() . '/plugins');

            $diDefinitions[Plugins::class] = $plugins;
            $diDefinitions = $plugins->registerServices($diDefinitions);
        } else {
            $plugins = null;
        }

        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->useAnnotations(true);
        $containerBuilder->useAutowiring(true);
        if ($environment->isProduction()) {
            $containerBuilder->enableCompilation($environment->getTempDirectory());
        }

        $containerBuilder->addDefinitions($diDefinitions);

        // Check for services.php file and include it if one exists.
        $config_dir = $environment->getConfigDirectory();
        if (file_exists($config_dir . '/services.php')) {
            $containerBuilder->addDefinitions($config_dir . '/services.php');
        }

        $di = $containerBuilder->build();

        $logger = $di->get(LoggerInterface::class);

        register_shutdown_function(
            function (LoggerInterface $logger): void {
                $error = error_get_last();
                $errno = $error["type"] ?? \E_ERROR;
                $errfile = $error["file"] ?? 'unknown';
                $errline = $error["line"] ?? 0;
                $errstr = $error["message"] ?? 'Shutdown';

                if ($errno &= \E_PARSE | \E_ERROR | \E_USER_ERROR | \E_CORE_ERROR | \E_COMPILE_ERROR) {
                    $logger->critical(
                        sprintf(
                            'Fatal error: %s in %s on line %d',
                            $errstr,
                            $errfile,
                            $errline
                        )
                    );
                }
            },
            $logger
        );

        Registry::addLogger($logger, 'app', true);

        return $di;
    }

    protected static function buildEnvironment(array $environment): Environment
    {
        if (!isset($environment[Environment::BASE_DIR])) {
            throw new Exception\BootstrapException('No base directory specified!');
        }

        $environment[Environment::IS_DOCKER] = file_exists(
            dirname($environment[Environment::BASE_DIR]) . '/.docker'
        );

        $environment[Environment::TEMP_DIR] ??= dirname($environment[Environment::BASE_DIR]) . '/www_tmp';
        $environment[Environment::CONFIG_DIR] ??= $environment[Environment::BASE_DIR] . '/config';
        $environment[Environment::VIEWS_DIR] ??= $environment[Environment::BASE_DIR] . '/templates';

        if ($environment[Environment::IS_DOCKER]) {
            $_ENV = getenv();
        } elseif (file_exists($environment[Environment::BASE_DIR] . '/env.ini')) {
            $_ENV = array_merge($_ENV, parse_ini_file($environment[Environment::BASE_DIR] . '/env.ini'));
        }

        $environment = array_merge(array_filter($_ENV), $environment);

        return new Environment($environment);
    }

    protected static function applyPhpSettings(Environment $environment): void
    {
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT);

        $displayStartupErrors = (!$environment->isProduction() || $environment->isCli())
            ? '1'
            : '0';
        ini_set('display_startup_errors', $displayStartupErrors);
        ini_set('display_errors', $displayStartupErrors);

        ini_set('log_errors', '1');
        ini_set(
            'error_log',
            $environment->isDocker()
                ? '/dev/stderr'
                : $environment->getTempDirectory() . '/php_errors.log'
        );
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_lifetime', '86400');
        ini_set('session.use_strict_mode', '1');

        date_default_timezone_set('UTC');

        session_cache_limiter('');
    }
}
