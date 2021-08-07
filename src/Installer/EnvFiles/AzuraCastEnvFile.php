<?php

declare(strict_types=1);

namespace App\Installer\EnvFiles;

use App\Environment;
use App\Locale;
use Psr\Log\LogLevel;

use function __;

class AzuraCastEnvFile extends AbstractEnvFile
{
    /** @inheritDoc */
    public static function getConfiguration(): array
    {
        static $config = null;

        if (null === $config) {
            $emptyEnv = new Environment([]);
            $defaults = $emptyEnv->toArray();

            $langOptions = [];
            foreach (Locale::SUPPORTED_LOCALES as $locale => $localeName) {
                $langOptions[] = Locale::stripLocaleEncoding($locale);
            }

            $dbSettings = $emptyEnv->getDatabaseSettings();
            $redisSettings = $emptyEnv->getRedisSettings();

            $config = [
                Environment::LANG => [
                    'name' => __(
                        'The locale to use for CLI commands.',
                    ),
                    'options' => $langOptions,
                    'default' => Locale::stripLocaleEncoding(Locale::DEFAULT_LOCALE),
                    'required' => true,
                ],
                Environment::APP_ENV => [
                    'name' => __(
                        'The application environment.',
                    ),
                    'options' => [
                        Environment::ENV_PRODUCTION,
                        Environment::ENV_DEVELOPMENT,
                        Environment::ENV_TESTING,
                    ],
                    'required' => true,
                ],
                Environment::LOG_LEVEL => [
                    'name' => __(
                        'Manually modify the logging level.',
                    ),
                    'description' => __(
                        'This allows you to log debug-level errors temporarily (for problem-solving) or reduce the volume of logs that are produced by your installation, without needing to modify whether your installation is a production or development instance.'
                    ),
                    'options' => [
                        LogLevel::DEBUG,
                        LogLevel::INFO,
                        LogLevel::NOTICE,
                        LogLevel::WARNING,
                        LogLevel::ERROR,
                        LogLevel::CRITICAL,
                        LogLevel::ALERT,
                        LogLevel::EMERGENCY,
                    ],
                ],
                'COMPOSER_PLUGIN_MODE' => [
                    'name' => __('Composer Plugin Mode'),
                    'description' => __(
                        'Enable the composer "merge" functionality to combine the main application\'s composer.json file with any plugin composer files. This can have performance implications, so you should only use it if you use one or more plugins with their own Composer dependencies.',
                    ),
                    'options' => [true, false],
                    'default' => false,
                ],
                Environment::AUTO_ASSIGN_PORT_MIN => [
                    'name' => __(
                        'Minimum Port for Station Port Assignment'
                    ),
                    'description' => __(
                        'Modify this if your stations are listening on nonstandard ports.',
                    ),
                ],
                Environment::AUTO_ASSIGN_PORT_MAX => [
                    'name' => __(
                        'Maximum Port for Station Port Assignment'
                    ),
                    'description' => __(
                        'Modify this if your stations are listening on nonstandard ports.',
                    ),
                ],
                Environment::DB_HOST => [
                    'name' => __('MariaDB Host'),
                    'description' => __(
                        'Do not modify this after installation.',
                    ),
                    'default' => $dbSettings['host'],
                    'required' => true,
                ],
                Environment::DB_PORT => [
                    'name' => __('MariaDB Port'),
                    'description' => __(
                        'Do not modify this after installation.',
                    ),
                    'default' => $dbSettings['port'],
                    'required' => true,
                ],
                Environment::DB_USER => [
                    'name' => __('MariaDB Username'),
                    'description' => __(
                        'Do not modify this after installation.',
                    ),
                    'default' => $dbSettings['user'],
                    'required' => true,
                ],
                Environment::DB_PASSWORD => [
                    'name' => __('MariaDB Password'),
                    'description' => __(
                        'Do not modify this after installation.',
                    ),
                    'default' => $dbSettings['password'],
                    'required' => true,
                ],
                Environment::DB_NAME => [
                    'name' => __('MariaDB Database Name'),
                    'description' => __(
                        'Do not modify this after installation.',
                    ),
                    'default' => $dbSettings['dbname'],
                    'required' => true,
                ],
                'MYSQL_RANDOM_ROOT_PASSWORD' => [
                    'name' => __('Auto-generate Random MariaDB Root Password'),
                    'description' => __(
                        'Do not modify this after installation.',
                    ),
                    'default' => 'yes',
                ],
                'MYSQL_SLOW_QUERY_LOG' => [
                    'name' => __('Enable MariaDB Slow Query Log'),
                    'description' => __(
                        'Log slower queries to diagnose possible database issues. Only turn this on if needed.',
                    ),
                    'default' => 0,
                ],
                'MYSQL_MAX_CONNECTIONS' => [
                    'name' => __('MariaDB Maximum Connections'),
                    'description' => __(
                        'Set the amount of allowed connections to the database. This value should be increased if you are seeing the "Too many connections" error in the logs.',
                    ),
                    'default' => 100,
                ],
                Environment::ENABLE_REDIS => [
                    'name' => __('Enable Redis'),
                    'description' => __(
                        'Disable to use a flatfile cache instead of Redis.',
                    ),
                ],
                Environment::REDIS_HOST => [
                    'name' => __('Redis Host'),
                    'default' => $redisSettings['host'],
                    'required' => true,
                ],
                Environment::REDIS_PORT => [
                    'name' => __('Redis Port'),
                    'default' => $redisSettings['port'],
                    'required' => true,
                ],
                Environment::REDIS_DB => [
                    'name' => __('Redis Database Index'),
                    'options' => range(0, 15),
                    'default' => $redisSettings['db'],
                    'required' => true,
                ],
                'PHP_MAX_FILE_SIZE' => [
                    'name' => __('PHP Maximum POST File Size'),
                    'default' => '25M',
                ],
                'PHP_MEMORY_LIMIT' => [
                    'name' => __('PHP Memory Limit'),
                    'default' => '128M',
                ],
                'PHP_MAX_EXECUTION_TIME' => [
                    'name' => __('PHP Script Maximum Execution Time'),
                    'description' => __('(in seconds)'),
                    'default' => 30,
                ],
                Environment::SYNC_SHORT_EXECUTION_TIME => [
                    'name' => __('Short Sync Task Execution Time'),
                    'description' => __(
                        'The maximum execution time (and lock timeout) for the 15-second, 1-minute and 5-minute synchronization tasks.'
                    ),
                ],
                Environment::SYNC_LONG_EXECUTION_TIME => [
                    'name' => __('Long Sync Task Execution Time'),
                    'description' => __(
                        'The maximum execution time (and lock timeout) for the 1-hour synchronization task.',
                    ),
                ],
                'PHP_FPM_MAX_CHILDREN' => [
                    'name' => __('Maximum PHP-FPM Worker Processes'),
                    'default' => 5,
                ],
                Environment::PROFILING_EXTENSION_ENABLED => [
                    'name' => __('Enable Performance Profiling Extension'),
                    'description' => __(
                        'Profiling data can be viewed by visiting %s.',
                        'http://your-azuracast-site/?SPX_KEY=dev&SPX_UI_URI=/',
                    ),
                ],
                Environment::PROFILING_EXTENSION_ALWAYS_ON => [
                    'name' => __('Profile Performance on All Requests'),
                    'description' => __(
                        'This will have a significant performance impact on your installation.',
                    ),
                ],
                Environment::PROFILING_EXTENSION_HTTP_KEY => [
                    'name' => __('Profiling Extension HTTP Key'),
                    'description' => __(
                        'The value for the "SPX_KEY" parameter for viewing profiling pages.',
                    ),
                ],
                'PROFILING_EXTENSION_HTTP_IP_WHITELIST' => [
                    'name' => __('Profiling Extension IP Allow List'),
                    'options' => ['127.0.0.1', '*'],
                    'default' => '*',
                ],
            ];

            foreach ($config as $key => &$keyInfo) {
                $keyInfo['default'] ??= $defaults[$key] ?? null;
            }
        }

        return $config;
    }

    public static function buildPathFromBase(string $baseDir): string
    {
        return $baseDir . DIRECTORY_SEPARATOR . 'azuracast.env';
    }
}
