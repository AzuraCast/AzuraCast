<?php
namespace App;

use App\Http\Response;
use App\Http\ServerRequest;
use Azura\App;
use Azura\Exception;
use Azura\Http\Factory\ResponseFactory;
use Azura\Http\Factory\ServerRequestFactory;
use Azura\Logger;
use DI;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Psr\Log\LoggerInterface;

class AppFactory extends \Azura\AppFactory
{
    /**
     * @inheritDoc
     */
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
        /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
        define('SAMPLE_TIMESTAMP', random_int(time() - 86400, time() + 86400));

        // Override DI definitions for settings.
        $diDefinitions[Settings::class] = $settings;
        $diDefinitions[\Azura\Settings::class] = DI\get(Settings::class);
        $diDefinitions['settings'] = DI\get(Settings::class);

        self::applyPhpSettings($settings);

        if ($autoloader) {
            $plugins = new Plugins($settings[Settings::BASE_DIR] . '/plugins');
            $plugins->registerAutoloaders($autoloader);

            $diDefinitions[Plugins::class] = $plugins;
            $diDefinitions = $plugins->registerServices($diDefinitions);
        } else {
            $plugins = null;
        }

        $di = self::buildContainer($settings, $diDefinitions);

        Logger::setInstance($di->get(LoggerInterface::class));

        // Set Response/Request decoratorclasses.
        ServerRequestFactory::setServerRequestClass(ServerRequest::class);
        ResponseFactory::setResponseClass(Response::class);

        $app = self::createFromContainer($di);
        $di->set(App::class, $app);
        $di->set(\Slim\App::class, $app);

        self::updateRouteHandling($app);
        self::buildRoutes($app);

        return $app;
    }

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

        return parent::buildSettings($settings);
    }
}
