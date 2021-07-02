<?php

namespace App\Console\Command;

use App\Environment;
use App\Locale;
use App\Radio\Configuration;
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
        array $config
    ): void {
        $values = array_filter($values);
    }

    protected function getEnvValue(
        mixed $value
    ): string {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }
}
