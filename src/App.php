<?php
namespace App;

use Azura\Settings;
use Azura\Exception;

class App extends \Azura\App
{
    public static function create(array $values): \Azura\App
    {
        $settings = $values['settings'] ?? [];

        if (!isset($settings[Settings::BASE_DIR])) {
            throw new Exception\Bootstrap('No base directory specified!');
        }

        $settings[Settings::TEMP_DIR] = dirname($settings[Settings::BASE_DIR]).'/www_tmp';

        // Define the "helper" constants used by AzuraCast.
        define('APP_IS_COMMAND_LINE', PHP_SAPI === 'cli');

        if (!defined('APP_TESTING_MODE')) {
            define('APP_TESTING_MODE', false);
        }

        define('APP_INCLUDE_ROOT', $settings[Settings::BASE_DIR]);
        define('APP_INCLUDE_TEMP', $settings[Settings::TEMP_DIR]);

        define('APP_INSIDE_DOCKER', file_exists(dirname($settings[Settings::BASE_DIR]).'/.docker'));
        define('APP_DOCKER_REVISION', getenv('AZURACAST_DC_REVISION') ?? 1);

        $settings[Settings::IS_DOCKER] = APP_INSIDE_DOCKER;

        // Register the plugins engine.
        $autoloader = $values['autoloader'];

        $plugins = new Plugins($settings[Settings::BASE_DIR].'/plugins');
        $plugins->registerAutoloaders($autoloader);

        $values[Plugins::class] = $plugins;
        $values['settings'] = $settings;

        $app = \Azura\App::create($values);
        $di = $app->getContainer();

        $settings = $di['settings'];

        define('APP_APPLICATION_ENV', $settings[Settings::APP_ENV]);
        define('APP_IN_PRODUCTION', $settings[Settings::IS_PRODUCTION]);

        $plugins->registerServices($di);

        return $app;
    }
}
