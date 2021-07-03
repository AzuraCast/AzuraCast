<?php

namespace App\Console\Command;

use App\Environment;
use App\Locale;
use App\Radio\Configuration;
use App\Utilities\Strings;
use Dotenv\Dotenv;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InstallCommand extends CommandAbstract
{
    public const WORKDIR = '/installer';

    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        Environment $environment
    ): int {
        $workDir = self::WORKDIR;

        // Initialize all the environment variables.
        $envPath = $workDir . '/.env';
        $azuracastEnvPath = $workDir . '/azuracast.env';

        if (is_file($envPath)) {
            $isNewInstall = false;
            $env = Dotenv::parse(file_get_contents($envPath));
        } else {
            $isNewInstall = true;
            $env = [];
        }

        if (is_file($azuracastEnvPath)) {
            $azuracastEnv = Dotenv::parse(file_get_contents($azuracastEnvPath));
        } else {
            $azuracastEnv = [];
        }

        // Initialize locale for translated installer/updater.
        if ($isNewInstall) {
            $currentLang = Locale::stripLocaleEncoding($environment->getLang());

            $lang = $io->choice(
                __('Select Language'),
                $this->getLangOptions($environment),
                $currentLang
            );

            $azuracastEnv[Environment::LANG] = $lang;

            $locale = new Locale($environment, $lang);
            $locale->register();
        }

        // Build defaults now (once new locale is registered)
        $azuracastEnvFileConfig = $this->getAzuraCastEnvFileConfig();
        $azuracastEnv = $this->applyDefaults($azuracastEnv, $azuracastEnvFileConfig);

        $envConfig = $this->getEnvFileConfig();
        $env = $this->applyDefaults($env, $envConfig);

        // Display header messages
        if ($isNewInstall) {
            $io->title(
                __('AzuraCast Installer')
            );
            $io->block(
                __('Welcome to AzuraCast! Complete the initial server setup by answering a few questions.')
            );

            $customize = $io->confirm(
                __('Customize server settings (ports, databases, etc.)?'),
                false
            );
        } else {
            $io->title(
                __('AzuraCast Updater')
            );

            $customize = $io->confirm(
                __('Modify server settings (ports, databases, etc.)?'),
                false
            );
        }

        if ($customize) {
            $io->writeln(
                __('AzuraCast is currently configured to listen on the following ports:'),
            );
            $io->listing(
                [
                    __('HTTP Port: %d', $env['AZURACAST_HTTP_PORT']),
                    __('HTTPS Port: %d', $env['AZURACAST_HTTPS_PORT']),
                    __('SFTP Port: %d', $env['AZURACAST_SFTP_PORT']),
                    __('Radio Ports: %s', $env['AZURACAST_STATION_PORTS']),
                ],
            );

            $customizePorts = $io->confirm(
                __('Customize ports used for AzuraCast?'),
                false
            );

            if ($customizePorts) {
                $simplePorts = [
                    'AZURACAST_HTTP_PORT',
                    'AZURACAST_HTTPS_PORT',
                    'AZURACAST_SFTP_PORT',
                ];

                foreach ($simplePorts as $port) {
                    $env[$port] = (int)$io->ask(
                        $envConfig[$port]['name'] . ' - ' . $envConfig[$port]['description'],
                        (int)$env[$port]
                    );
                }
            }
        }

        $io->writeln(
            __('Writing configuration files...')
        );

        $this->writeEnvFile($envPath, $env, $envConfig);
        $this->writeEnvFile($azuracastEnvPath, $azuracastEnv, $azuracastEnvFileConfig);

        $io->success(
            __('Server configuration complete!')
        );
        return 0;
    }

    protected function applyDefaults(
        array $currentVars,
        array $config,
    ): array {
        $currentVars = array_filter($currentVars);

        $defaults = [];
        foreach ($config as $key => $keyInfo) {
            if (isset($keyInfo['default'])) {
                $defaults[$key] = $keyInfo['default'] ?? null;
            }
        }

        return array_merge($defaults, $currentVars);
    }

    protected function getEnvFileConfig(): array
    {
        $defaultPorts = [];
        for ($i = Configuration::DEFAULT_PORT_MIN; $i < Configuration::DEFAULT_PORT_MAX; $i += 10) {
            if (in_array($i, Configuration::PROTECTED_PORTS, true)) {
                continue;
            }

            $defaultPorts[] = $i;
            $defaultPorts[] = $i + 5;
            $defaultPorts[] = $i + 6;
        }

        return [
            'COMPOSE_PROJECT_NAME' => [
                'name' => __(
                    '(Docker Compose) All Docker containers are prefixed by this name. Do not change this after installation.'
                ),
                'default' => 'azuracast',
            ],
            'COMPOSE_HTTP_TIMEOUT' => [
                'name' => __(
                    '(Docker Compose) The amount of time to wait before a Docker Compose operation fails. Increase this on lower performance computers.'
                ),
                'default' => 300,
            ],
            'AZURACAST_VERSION' => [
                'name' => __('AzuraCast Release Channel'),
                'options' => ['latest', 'stable'],
                'default' => 'latest',
            ],
            'AZURACAST_HTTP_PORT' => [
                'name' => __('HTTP Port'),
                'description' => __(
                    'The main port AzuraCast listens to for insecure HTTP connections.',
                ),
                'default' => 80,
            ],
            'AZURACAST_HTTPS_PORT' => [
                'name' => __('HTTPS Port'),
                'description' => __(
                    'The main port AzuraCast listens to for secure HTTPS connections.',
                ),
                'default' => 443,
            ],
            'AZURACAST_SFTP_PORT' => [
                'name' => __('SFTP Port'),
                'description' => __(
                    'The port AzuraCast listens to for SFTP file management connections.',
                ),
                'default' => 2022,
            ],
            'AZURACAST_STATION_PORTS' => [
                'name' => __('Station Ports'),
                'description' => __(
                    'The ports AzuraCast should listen to for station broadcasts and incoming DJ connections.',
                ),
                'default' => implode(',', $defaultPorts),
            ],
            'AZURACAST_PUID' => [
                'name' => __('Docker User UID'),
                'description' => __(
                    'Set the UID of the user running inside the Docker containers. Matching this with your host UID can fix permission issues.',
                ),
                'default' => 1000,
            ],
            'AZURACAST_PGID' => [
                'name' => __('Docker User GID'),
                'description' => __(
                    'Set the GID of the user running inside the Docker containers. Matching this with your host GID can fix permission issues.'
                ),
                'default' => 1000,
            ],

        ];
    }

    protected function getAzuraCastEnvFileConfig(): array
    {
        $emptyEnv = new Environment([]);
        $defaults = $emptyEnv->toArray();

        $config = [
            Environment::LANG => [
                'name' => __(
                    'The locale to use for CLI commands.',
                ),
                'options' => $this->getLangOptions($emptyEnv),
                'default' => Locale::stripLocaleEncoding(Locale::DEFAULT_LOCALE),
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
            ],
            Environment::DB_PORT => [
                'name' => __('MariaDB Port'),
                'description' => __(
                    'Do not modify this after installation.',
                ),
            ],
            Environment::DB_USER => [
                'name' => __('MariaDB Username'),
                'description' => __(
                    'Do not modify this after installation.',
                ),
            ],
            Environment::DB_PASSWORD => [
                'name' => __('MariaDB Password'),
                'description' => __(
                    'Do not modify this after installation.',
                ),
            ],
            Environment::DB_NAME => [
                'name' => __('MariaDB Database Name'),
                'description' => __(
                    'Do not modify this after installation.',
                ),
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
            ],
            Environment::REDIS_PORT => [
                'name' => __('Redis Port'),
            ],
            Environment::REDIS_DB => [
                'name' => __('Redis Database Index'),
                'options' => range(0, 15),
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
                'default' => '127.0.0.1',
            ],
        ];

        foreach ($config as $key => &$keyInfo) {
            $keyInfo['default'] ??= $defaults[$key] ?? null;
        }

        return $config;
    }

    protected function getLangOptions(Environment $env): array
    {
        $langOptions = [];
        foreach ($env->getSupportedLocales() as $langKey => $langName) {
            $langOptions[Locale::stripLocaleEncoding($langKey)] = $langName;
        }
        return $langOptions;
    }

    protected function writeEnvFile(
        string $path,
        array $values,
        array $config,
        bool $debug = true
    ): void {
        $values = array_filter($values);

        $envFile = [
            '# ' . __('This file was automatically generated by AzuraCast.'),
            '# ' . __('You can modify it as necessary. To apply changes, restart the Docker containers.'),
            '# ' . __('Remove the leading "#" symbol from lines to uncomment them.'),
            '',
        ];

        foreach ($config as $key => $keyInfo) {
            $envFile[] = '# ' . ($keyInfo['name'] ?? $key);

            if (!empty($keyInfo['description'])) {
                $desc = Strings::mbWordwrap($keyInfo['description']);

                foreach (explode("\n", $desc) as $descPart) {
                    $envFile[] = '# ' . $descPart;
                }
            }

            if (!empty($keyInfo['options'])) {
                $options = array_map(
                    fn($val) => $this->getEnvValue($val),
                    $keyInfo['options'],
                );

                $envFile[] = '# ' . __('Valid options: %s', implode(', ', $options));
            }

            $value = (isset($values[$key]))
                ? $this->getEnvValue($values[$key])
                : null;

            if (!empty($keyInfo['default'])) {
                $default = $this->getEnvValue($keyInfo['default']);
                $envFile[] = '# ' . __('Default: %s', $default);
            } else {
                $default = '';
            }

            if (null === $value || $default === $value) {
                $value ??= $default;
                $envFile[] = '# ' . $key . '=' . $value;
            } else {
                $envFile[] = $key . '=' . $value;
            }

            $envFile[] = '';
        }

        $envFileStr = implode("\n", $envFile);

        if ($debug) {
            print_r($envFileStr);
        }

        file_put_contents($path, $envFileStr);
    }

    protected function getEnvValue(
        mixed $value
    ): string {
        if (is_null($value)) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value)) {
            return (string)$value;
        }
        if (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }
}
