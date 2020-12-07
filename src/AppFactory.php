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
    public static function create($autoloader = null, $appEnvironment = [], $diDefinitions = []): App
    {
        // Register Annotation autoloader
        if (null !== $autoloader) {
            AnnotationRegistry::registerLoader([$autoloader, 'loadClass']);
        }

        $environment = new Environment(self::buildEnvironment($appEnvironment));
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

        $di = self::buildContainer($environment, $diDefinitions);

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
    protected static function buildEnvironment(array $environment): array
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

        return $environment;
    }

    protected static function applyPhpSettings(Environment $environment): void
    {
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT);

        ini_set('display_startup_errors', !$environment->isProduction() ? '1' : '0');
        ini_set('display_errors', !$environment->isProduction() ? '1' : '0');
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

    protected static function buildContainer(Environment $environment, array $diDefinitions = []): DI\Container
    {
        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->useAnnotations(true);
        $containerBuilder->useAutowiring(true);

        if ($environment->isProduction()) {
            $containerBuilder->enableCompilation($environment->getTempDirectory());
        }

        if (!isset($diDefinitions[Environment::class])) {
            $diDefinitions[Environment::class] = $environment;
        }

        $containerBuilder->addDefinitions($diDefinitions);

        // Check for services.php file and include it if one exists.
        $config_dir = $environment->getConfigDirectory();
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

        $environment = $di->get(Environment::class);

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

        if ($environment->isProduction()) {
            $routeCollector->setCacheFile($environment->getTempDirectory() . '/app_routes.cache.php');
        }
    }

    /**
     * @param App $app
     */
    protected static function buildRoutes(App $app): void
    {
        $di = $app->getContainer();

        $dispatcher = $di->get(EventDispatcher::class);
        $dispatcher->dispatch(new Event\BuildRoutes($app));
    }
}
