<?php
namespace App;

use App\Http\Response;
use App\Http\ServerRequest;
use Azura\App;
use Azura\Exception;
use Azura\Http\Factory\ResponseFactory;
use Azura\Http\Factory\ServerRequestFactory;
use Azura\Settings;
use Doctrine\Common\Annotations\AnnotationRegistry;

class AppFactory extends \Azura\AppFactory
{
    /**
     * @inheritDoc
     */
    public static function create($autoloader = null, $settings = [], $diDefinitions = []): App
    {
        // Register Annotation autoloader
        if (null !== $autoloader) {
            AnnotationRegistry::registerLoader([$autoloader, 'loadClass']);
        }

        $settings = self::buildSettings($settings);

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

        // Set Response/Request decoratorclasses.
        ServerRequestFactory::setServerRequestClass(ServerRequest::class);
        ResponseFactory::setResponseClass(Response::class);

        $app = self::createFromContainer($di);
        $di->set(App::class, $app);
        $di->set(\Slim\App::class, $app);

        self::updateRouteHandling($app);
        self::buildRoutes($app);

        $settings = $di->get(Settings::class);

        define('APP_APPLICATION_ENV', $settings[Settings::APP_ENV]);
        define('APP_IN_PRODUCTION', $settings->isProduction());

        return $app;
    }

    protected static function buildSettings($settings): Settings
    {
        if (!isset($settings[Settings::BASE_DIR])) {
            throw new Exception\Bootstrap('No base directory specified!');
        }

        $settings[Settings::TEMP_DIR] = dirname($settings[Settings::BASE_DIR]) . '/www_tmp';

        // Define the "helper" constants used by AzuraCast.
        define('APP_IS_COMMAND_LINE', PHP_SAPI === 'cli');

        define('APP_INCLUDE_ROOT', $settings[Settings::BASE_DIR]);
        define('APP_INCLUDE_TEMP', $settings[Settings::TEMP_DIR]);

        define('APP_INSIDE_DOCKER', file_exists(dirname($settings[Settings::BASE_DIR]) . '/.docker'));
        define('APP_DOCKER_REVISION', getenv('AZURACAST_DC_REVISION') ?? 1);

        $settings[Settings::IS_DOCKER] = APP_INSIDE_DOCKER;

        define('APP_TESTING_MODE',
            (isset($settings[Settings::APP_ENV]) && Settings::ENV_TESTING === $settings[Settings::APP_ENV]));

        // Constants used in annotations
        define('SAMPLE_TIMESTAMP', rand(time() - 86400, time() + 86400));

        return parent::buildSettings($settings);
    }
}
