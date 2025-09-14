<?php

declare(strict_types=1);

namespace App;

use App\Console\Application;
use App\Enums\SupportedLocales;
use App\Http\HttpFactory;
use App\Utilities\Logger as AppLogger;
use DI;
use Dotenv\Dotenv;
use Monolog\ErrorHandler;
use Monolog\Logger;
use Monolog\Registry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Slim\App;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Handlers\Strategies\RequestResponse;
use Throwable;

/**
 * @phpstan-type AppWithContainer App<DI\Container>
 */
final class AppFactory
{
    public const int ERROR_REPORTING_DEVELOPMENT = E_ALL & ~E_DEPRECATED;
    public const int ERROR_REPORTING_PRODUCTION = self::ERROR_REPORTING_DEVELOPMENT & ~E_NOTICE & ~E_WARNING;

    /**
     * @return AppWithContainer
     */
    public static function createApp(
        array $appEnvironment = []
    ): App {
        $environment = self::buildEnvironment($appEnvironment);
        $diBuilder = self::createContainerBuilder($environment);
        $di = self::buildContainer($diBuilder);
        return self::buildAppFromContainer($di);
    }

    public static function createCli(
        array $appEnvironment = []
    ): Application {
        $environment = self::buildEnvironment($appEnvironment);
        $diBuilder = self::createContainerBuilder($environment);
        $di = self::buildContainer($diBuilder);

        // Some CLI commands require the App to be injected for routing.
        self::buildAppFromContainer($di);

        SupportedLocales::createForCli($environment);

        return $di->get(Application::class);
    }

    /**
     * @return AppWithContainer
     */
    public static function buildAppFromContainer(
        DI\Container $container,
        ?HttpFactory $httpFactory = null
    ): App {
        $httpFactory ??= new HttpFactory();

        ServerRequestCreatorFactory::setSlimHttpDecoratorsAutomaticDetection(false);
        ServerRequestCreatorFactory::setServerRequestCreator($httpFactory);

        $app = new App(
            responseFactory: $httpFactory,
            container: $container,
        );
        $container->set(App::class, $app);

        $routeCollector = $app->getRouteCollector();
        $routeCollector->setDefaultInvocationStrategy(new RequestResponse());

        $environment = $container->get(Environment::class);
        if ($environment->isProduction()) {
            $routeCollector->setCacheFile($environment->getTempDirectory() . '/app_routes.cache.php');
        }

        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(new Event\BuildRoutes($app, $container));

        return $app;
    }

    /**
     * @return DI\ContainerBuilder<DI\Container>
     */
    public static function createContainerBuilder(
        Environment $environment
    ): DI\ContainerBuilder {
        $diDefinitions = [
            Environment::class => $environment,
        ];

        Environment::setInstance($environment);

        // Override DI definitions for settings.
        $plugins = new Plugins($environment->getBaseDirectory() . '/plugins');

        $diDefinitions[Plugins::class] = $plugins;
        $diDefinitions = $plugins->registerServices($diDefinitions);

        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAttributes(true);

        $containerBuilder->addDefinitions($diDefinitions);

        $containerBuilder->addDefinitions(dirname(__DIR__) . '/config/services.php');

        return $containerBuilder;
    }

    /**
     * @param DI\ContainerBuilder<DI\Container> $containerBuilder
     * @return DI\Container
     */
    public static function buildContainer(
        DI\ContainerBuilder $containerBuilder
    ): DI\Container {
        $di = $containerBuilder->build();

        // Monolog setup
        $logger = $di->get(Logger::class);
        $errorHandler = new ErrorHandler($logger);
        $errorHandler->registerFatalHandler();

        Registry::addLogger($logger, AppLogger::INSTANCE_NAME, true);

        return $di;
    }

    /**
     * @param array<string, mixed> $rawEnvironment
     */
    public static function buildEnvironment(array $rawEnvironment = []): Environment
    {
        $_ENV = getenv();
        $rawEnvironment = array_merge(array_filter($_ENV), $rawEnvironment);
        $environment = new Environment($rawEnvironment);

        // Try to load from .env file
        if ($environment->isDevelopment() || !$environment->isDocker()) {
            $envFile = $environment->getBaseDirectory() . '/azuracast.env';

            if (file_exists($envFile)) {
                $fileContents = file_get_contents($envFile);

                if (!empty($fileContents)) {
                    try {
                        $envFileContents = array_filter(Dotenv::parse($fileContents));
                        $rawEnvironment = array_merge($rawEnvironment, $envFileContents);

                        $environment = new Environment($rawEnvironment);
                    } catch (Throwable) {
                    }
                }
            }
        }

        self::applyPhpSettings($environment);

        return $environment;
    }

    private static function applyPhpSettings(Environment $environment): void
    {
        error_reporting(
            $environment->isProduction()
                ? self::ERROR_REPORTING_PRODUCTION
                : self::ERROR_REPORTING_DEVELOPMENT
        );

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

        mb_internal_encoding('UTF-8');
        ini_set('default_charset', 'utf-8');

        if (!headers_sent()) {
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_lifetime', '86400');
            ini_set('session.use_strict_mode', '1');

            session_cache_limiter('');
        }

        date_default_timezone_set('UTC');
    }
}
