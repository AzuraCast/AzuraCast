<?php
namespace App;

use Azura\Settings;
use Azura\Exception;
use Doctrine\Common\Annotations\AnnotationRegistry;

class App extends \Azura\App
{
    public static function create(array $values): \Azura\App
    {
        $settings = $values['settings'] ?? [];

        if (!isset($settings[Settings::BASE_DIR])) {
            throw new Exception\Bootstrap('No base directory specified!');
        }

        $settings[Settings::TEMP_DIR] = dirname($settings[Settings::BASE_DIR]) . '/www_tmp';
        $settings[Settings::VIEWS_DIR] = $settings[Settings::BASE_DIR] . '/resources/templates';

        // Define the "helper" constants used by AzuraCast.
        define('APP_IS_COMMAND_LINE', PHP_SAPI === 'cli');

        define('APP_INCLUDE_ROOT', $settings[Settings::BASE_DIR]);
        define('APP_INCLUDE_TEMP', $settings[Settings::TEMP_DIR]);

        define('APP_INSIDE_DOCKER', file_exists(dirname($settings[Settings::BASE_DIR]) . '/.docker'));
        define('APP_DOCKER_REVISION', getenv('AZURACAST_DC_REVISION') ?? 1);

        $settings[Settings::IS_DOCKER] = APP_INSIDE_DOCKER;

        define('APP_TESTING_MODE', (isset($settings[Settings::APP_ENV]) && Settings::ENV_TESTING === $settings[Settings::APP_ENV]));

        // Constants used in annotations
        define('AZURACAST_VERSION', Version::FALLBACK_VERSION);
        define('SAMPLE_TIMESTAMP', rand(time() - 86400, time() + 86400));

        // Register the plugins engine.
        if (isset($values['autoloader'])) {
            $autoloader = $values['autoloader'];

            AnnotationRegistry::registerLoader([$autoloader, 'loadClass']);

            $plugins = new Plugins($settings[Settings::BASE_DIR] . '/plugins');
            $plugins->registerAutoloaders($autoloader);

            $values[Plugins::class] = $plugins;
        } else {
            $plugins = null;
        }

        $values['settings'] = $settings;

        $app = \Azura\App::create($values);
        $di = $app->getContainer();

        /** @var Settings $settings */
        $settings = $di['settings'];

        define('APP_APPLICATION_ENV', $settings[Settings::APP_ENV]);
        define('APP_IN_PRODUCTION', $settings->isProduction());

        if (null !== $plugins) {
            $plugins->registerServices($di);
        }

        return $app;
    }
}
