<?php

declare(strict_types=1);

namespace App\Installer\Command;

use App\Enums\SupportedLocales;
use App\Environment;
use App\Installer\EnvFiles\AbstractEnvFile;
use App\Installer\EnvFiles\AzuraCastEnvFile;
use App\Installer\EnvFiles\EnvFile;
use App\Radio\Configuration;
use App\Utilities\Strings;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'install'
)]
class InstallCommand extends Command
{
    public const DEFAULT_BASE_DIRECTORY = '/installer';

    public function __construct(
        protected Environment $environment
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('base-dir', InputArgument::OPTIONAL)
            ->addOption('update', null, InputOption::VALUE_NONE)
            ->addOption('defaults', null, InputOption::VALUE_NONE)
            ->addOption('http-port', null, InputOption::VALUE_OPTIONAL)
            ->addOption('https-port', null, InputOption::VALUE_OPTIONAL)
            ->addOption('release-channel', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $baseDir = $input->getArgument('base-dir') ?? self::DEFAULT_BASE_DIRECTORY;
        $update = (bool)$input->getOption('update');
        $defaults = (bool)$input->getOption('defaults');
        $httpPort = $input->getOption('http-port');
        $httpsPort = $input->getOption('https-port');
        $releaseChannel = $input->getOption('release-channel');

        $devMode = ($baseDir !== self::DEFAULT_BASE_DIRECTORY);

        // Initialize all the environment variables.
        $envPath = EnvFile::buildPathFromBase($baseDir);
        $azuracastEnvPath = AzuraCastEnvFile::buildPathFromBase($baseDir);

        // Fail early if permissions aren't present.
        if (!is_writable($envPath)) {
            $io->error(
                'Permissions error: cannot write to work directory. Exiting installer and using defaults instead.'
            );
            return 1;
        }

        $isNewInstall = !$update;

        try {
            $env = EnvFile::fromEnvFile($envPath);
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            $env = new EnvFile($envPath);
        }

        try {
            $azuracastEnv = AzuraCastEnvFile::fromEnvFile($azuracastEnvPath);
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            $azuracastEnv = new AzuraCastEnvFile($envPath);
        }

        // Initialize locale for translated installer/updater.
        if (!$defaults && ($isNewInstall || empty($azuracastEnv[Environment::LANG]))) {
            $langOptions = [];
            foreach (SupportedLocales::cases() as $supportedLocale) {
                $langOptions[$supportedLocale->getLocaleWithoutEncoding()] = $supportedLocale->getLocalName();
            }

            $azuracastEnv[Environment::LANG] = $io->choice(
                'Select Language',
                $langOptions,
                SupportedLocales::default()->getLocaleWithoutEncoding()
            );
        }

        $locale = SupportedLocales::getValidLocale($azuracastEnv[Environment::LANG] ?? null);
        $locale->register($this->environment);

        $envConfig = EnvFile::getConfiguration($this->environment);
        $env->setFromDefaults($this->environment);

        $azuracastEnvConfig = AzuraCastEnvFile::getConfiguration($this->environment);
        $azuracastEnv->setFromDefaults($this->environment);

        // Apply values passed via flags
        if (null !== $releaseChannel) {
            $env['AZURACAST_VERSION'] = $releaseChannel;
        }
        if (null !== $httpPort) {
            $env['AZURACAST_HTTP_PORT'] = $httpPort;
        }
        if (null !== $httpsPort) {
            $env['AZURACAST_HTTPS_PORT'] = $httpsPort;
        }

        // Migrate legacy config values.
        if (isset($azuracastEnv['PREFER_RELEASE_BUILDS'])) {
            $env['AZURACAST_VERSION'] = ('true' === $azuracastEnv['PREFER_RELEASE_BUILDS'])
                ? 'stable'
                : 'latest';

            unset($azuracastEnv['PREFER_RELEASE_BUILDS']);
        }

        unset($azuracastEnv['ENABLE_ADVANCED_FEATURES']);

        // Randomize the MariaDB root password for new installs.
        if ($isNewInstall) {
            if ($devMode) {
                if (empty($azuracastEnv['MYSQL_ROOT_PASSWORD'])) {
                    $azuracastEnv['MYSQL_ROOT_PASSWORD'] = 'azur4c457_root';
                }
            } else {
                if (
                    empty($azuracastEnv[Environment::DB_PASSWORD])
                    || 'azur4c457' === $azuracastEnv[Environment::DB_PASSWORD]
                ) {
                    $azuracastEnv[Environment::DB_PASSWORD] = Strings::generatePassword(12);
                }

                if (empty($azuracastEnv['MYSQL_ROOT_PASSWORD'])) {
                    $azuracastEnv['MYSQL_ROOT_PASSWORD'] = Strings::generatePassword(20);
                }
            }
        }

        if (!empty($azuracastEnv['MYSQL_ROOT_PASSWORD'])) {
            unset($azuracastEnv['MYSQL_RANDOM_ROOT_PASSWORD']);
        } else {
            $azuracastEnv['MYSQL_RANDOM_ROOT_PASSWORD'] = 'yes';
        }

        // Special fixes for transitioning to standalone installations.
        if ($this->environment->isDockerStandalone()) {
            if ('mariadb' === $azuracastEnv['MYSQL_HOST']) {
                unset($azuracastEnv['MYSQL_HOST']);
            }
            if ('redis' === $azuracastEnv['REDIS_HOST']) {
                unset($azuracastEnv['REDIS_HOST']);
            }
        }

        // Display header messages
        if ($isNewInstall) {
            $io->title(
                __('AzuraCast Installer')
            );
            $io->block(
                __('Welcome to AzuraCast! Complete the initial server setup by answering a few questions.')
            );

            $customize = !$defaults;
        } else {
            $io->title(
                __('AzuraCast Updater')
            );

            if ($defaults) {
                $customize = false;
            } else {
                $customize = $io->confirm(
                    __('Change installation settings?'),
                    false
                );
            }
        }

        if ($customize) {
            // Port customization
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
                        (string)$env[$port]
                    );
                }

                $azuracastEnv[Environment::AUTO_ASSIGN_PORT_MIN] = (int)$io->ask(
                    $azuracastEnvConfig[Environment::AUTO_ASSIGN_PORT_MIN]['name'],
                    (string)$azuracastEnv[Environment::AUTO_ASSIGN_PORT_MIN]
                );

                $azuracastEnv[Environment::AUTO_ASSIGN_PORT_MAX] = (int)$io->ask(
                    $azuracastEnvConfig[Environment::AUTO_ASSIGN_PORT_MAX]['name'],
                    (string)$azuracastEnv[Environment::AUTO_ASSIGN_PORT_MAX]
                );

                $stationPorts = Configuration::enumerateDefaultPorts(
                    rangeMin: $azuracastEnv[Environment::AUTO_ASSIGN_PORT_MIN],
                    rangeMax: $azuracastEnv[Environment::AUTO_ASSIGN_PORT_MAX]
                );
                $env['AZURACAST_STATION_PORTS'] = implode(',', $stationPorts);
            }

            $customizeLetsEncrypt = $io->confirm(
                __('Set up LetsEncrypt?'),
                false
            );

            if ($customizeLetsEncrypt) {
                $env['LETSENCRYPT_HOST'] = $io->ask(
                    $envConfig['LETSENCRYPT_HOST']['description'],
                    $env['LETSENCRYPT_HOST'] ?? ''
                );

                $env['LETSENCRYPT_EMAIL'] = $io->ask(
                    $envConfig['LETSENCRYPT_EMAIL']['description'],
                    $env['LETSENCRYPT_EMAIL'] ?? ''
                );
            }
        }

        $io->writeln(
            __('Writing configuration files...')
        );

        $envStr = $env->writeToFile($this->environment);
        $azuracastEnvStr = $azuracastEnv->writeToFile($this->environment);

        if ($io->isVerbose()) {
            $io->section($env->getBasename());
            $io->block($envStr);

            $io->section($azuracastEnv->getBasename());
            $io->block($azuracastEnvStr);
        }

        $dockerComposePath = ($devMode)
            ? $baseDir . '/docker-compose.yml'
            : $baseDir . '/docker-compose.new.yml';
        $dockerComposeStr = $this->updateDockerCompose($dockerComposePath, $env, $azuracastEnv);

        if ($io->isVerbose()) {
            $io->section(basename($dockerComposePath));
            $io->block($dockerComposeStr);
        }

        $io->success(
            __('Server configuration complete!')
        );
        return 0;
    }

    protected function updateDockerCompose(
        string $dockerComposePath,
        AbstractEnvFile $env,
        AbstractEnvFile $azuracastEnv
    ): string {
        // Attempt to parse Docker Compose YAML file
        $sampleFile = $this->environment->getBaseDirectory() . '/docker-compose.sample.yml';
        $yaml = Yaml::parseFile($sampleFile);

        $isStandalone = $this->environment->isDockerStandalone();

        // Parse port listing and convert into YAML format.
        $ports = $env['AZURACAST_STATION_PORTS'] ?? '';

        $envConfig = $env::getConfiguration($this->environment);
        $defaultPorts = $envConfig['AZURACAST_STATION_PORTS']['default'];

        if (!empty($ports) && 0 !== strcmp($ports, $defaultPorts)) {
            $yamlPorts = [];
            $nginxRadioPorts = [];
            $nginxWebDjPorts = [];

            foreach (explode(',', $ports) as $port) {
                $port = (int)$port;
                if ($port <= 0) {
                    continue;
                }

                $yamlPorts[] = $port . ':' . $port;

                if (0 === $port % 10) {
                    $nginxRadioPorts[] = $port;
                } elseif (5 === $port % 10) {
                    $nginxWebDjPorts[] = $port;
                }
            }

            if (!empty($yamlPorts)) {
                if ($isStandalone) {
                    $existingPorts = [];
                    foreach ($yaml['services']['ports'] as $port) {
                        if (str_starts_with('$', $port)) {
                            $existingPorts[] = $port;
                        }
                    }

                    $yaml['services']['web']['ports'] = array_merge($existingPorts, $yamlPorts);
                } else {
                    $yaml['services']['stations']['ports'] = $yamlPorts;
                }
            }
            if (!empty($nginxRadioPorts)) {
                $nginxRadioPortsStr = '(' . implode('|', $nginxRadioPorts) . ')';
                $yaml['services']['web']['environment']['NGINX_RADIO_PORTS'] = $nginxRadioPortsStr;
            }
            if (!empty($nginxWebDjPorts)) {
                $nginxWebDjPortsStr = '(' . implode('|', $nginxWebDjPorts) . ')';
                $yaml['services']['web']['environment']['NGINX_WEBDJ_PORTS'] = $nginxWebDjPortsStr;
            }
        }

        // Remove Redis if it's not enabled.
        if (!$isStandalone) {
            $enableRedis = $azuracastEnv->getAsBool(Environment::ENABLE_REDIS, true);
            if (!$enableRedis) {
                unset($yaml['services']['redis']);
            }
        }

        // Remove privileged-mode settings if not enabled.
        $enablePrivileged = $env->getAsBool('AZURACAST_COMPOSE_PRIVILEGED', true);
        if (!$enablePrivileged) {
            foreach ($yaml['services'] as &$service) {
                unset(
                    $service['ulimits'],
                    $service['sysctls']
                );
            }
            unset($service);
        }

        $yamlRaw = Yaml::dump($yaml, PHP_INT_MAX);
        file_put_contents($dockerComposePath, $yamlRaw);

        return $yamlRaw;
    }
}
