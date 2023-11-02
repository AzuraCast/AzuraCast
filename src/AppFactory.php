<?php

declare(strict_types=1);

namespace App;

use App\Console\Application;
use App\Enums\SupportedLocales;
use App\Http\Factory\ResponseFactory;
use App\Http\Factory\ServerRequestFactory;
use App\Utilities\File;
use App\Utilities\Logger as AppLogger;
use DI;
use Monolog\ErrorHandler;
use Monolog\Logger;
use Monolog\Registry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Slim\App;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Handlers\Strategies\RequestResponse;

final class AppFactory
{
    public static function createApp(
        array $appEnvironment = [],
        array $diDefinitions = []
    ): App {
        $di = self::buildContainer($appEnvironment, $diDefinitions);
        return self::buildAppFromContainer($di);
    }

    public static function createCli(
        array $appEnvironment = [],
        array $diDefinitions = []
    ): Application {
        $di = self::buildContainer($appEnvironment, $diDefinitions);
        self::buildAppFromContainer($di);

        $env = $di->get(Environment::class);

        SupportedLocales::createForCli($env);

        return $di->get(Application::class);
    }

    public static function buildAppFromContainer(DI\Container $container): App
    {
        ServerRequestCreatorFactory::setSlimHttpDecoratorsAutomaticDetection(false);
        ServerRequestCreatorFactory::setServerRequestCreator(new ServerRequestFactory());

        $app = new App(
            responseFactory: new ResponseFactory(),
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

    public static function buildContainer(
        array $appEnvironment = [],
        array $diDefinitions = []
    ): DI\Container {
        $environment = self::buildEnvironment($appEnvironment);
        Environment::setInstance($environment);

        self::applyPhpSettings($environment);

        // Override DI definitions for settings.
        $diDefinitions[Environment::class] = $environment;

        $plugins = new Plugins($environment->getBaseDirectory() . '/plugins');

        $diDefinitions[Plugins::class] = $plugins;
        $diDefinitions = $plugins->registerServices($diDefinitions);

        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAttributes(true);

        if ($environment->isProduction()) {
            $containerBuilder->enableCompilation($environment->getTempDirectory());
        }

        $containerBuilder->addDefinitions($diDefinitions);

        // Check for services.php file and include it if one exists.
        $configDir = $environment->getConfigDirectory();
        if (file_exists($configDir . '/services.php')) {
            $containerBuilder->addDefinitions($configDir . '/services.php');
        }

        $di = $containerBuilder->build();

        // Monolog setup
        $logger = $di->get(Logger::class);
        $errorHandler = new ErrorHandler($logger);
        $errorHandler->registerFatalHandler();

        Registry::addLogger($logger, AppLogger::INSTANCE_NAME, true);

        return $di;
    }

    /**
     * @param array<string, mixed> $environment
     */
    public static function buildEnvironment(array $environment = []): Environment
    {
        if (!isset($environment[Environment::BASE_DIR])) {
            throw new Exception\BootstrapException('No base directory specified!');
        }

        $baseDir = $environment[Environment::BASE_DIR];
        $parentBaseDir = dirname($baseDir);

        $environment[Environment::IS_DOCKER] = file_exists($parentBaseDir . '/.docker');

        $environment[Environment::TEMP_DIR] ??= $parentBaseDir . '/www_tmp';
        $environment[Environment::CONFIG_DIR] ??= $baseDir . '/config';
        $environment[Environment::VIEWS_DIR] ??= $baseDir . '/templates';
        $environment[Environment::UPLOADS_DIR] ??= File::getFirstExistingDirectory([
            $parentBaseDir . '/storage/uploads',
            $parentBaseDir . '/uploads',
        ]);

        $_ENV = getenv();

        if (!$environment[Environment::IS_DOCKER]) {
            $envPaths = [
                $parentBaseDir . '/env.ini',
                $baseDir . '/env.ini',
            ];

            foreach ($envPaths as $envPath) {
                if (file_exists($envPath)) {
                    $envIni = parse_ini_file($envPath);
                    if (false !== $envIni) {
                        $_ENV = array_merge($_ENV, $envIni);
                        break;
                    }
                }
            }
        }

        $environment = array_merge(array_filter($_ENV), $environment);

        return new Environment($environment);
    }

    private static function applyPhpSettings(Environment $environment): void
    {
        error_reporting(
            $environment->isProduction()
                ? E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_DEPRECATED
                : E_ALL & ~E_NOTICE
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
