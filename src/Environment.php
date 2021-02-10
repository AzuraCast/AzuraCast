<?php

namespace App;

use App\Radio\Configuration;
use App\Traits\AvailableStaticallyTrait;
use Psr\Log\LogLevel;

class Environment
{
    use AvailableStaticallyTrait;

    protected array $data = [];

    // Environments
    public const ENV_DEVELOPMENT = 'development';
    public const ENV_TESTING = 'testing';
    public const ENV_PRODUCTION = 'production';

    // Core settings values
    public const APP_NAME = 'APP_NAME';
    public const APP_ENV = 'APPLICATION_ENV';

    public const BASE_DIR = 'BASE_DIR';
    public const TEMP_DIR = 'TEMP_DIR';
    public const CONFIG_DIR = 'CONFIG_DIR';
    public const VIEWS_DIR = 'VIEWS_DIR';

    public const IS_DOCKER = 'IS_DOCKER';
    public const IS_CLI = 'IS_CLI';

    public const ASSET_URL = 'ASSETS_URL';

    public const DOCKER_REVISION = 'AZURACAST_DC_REVISION';

    public const LANG = 'LANG';
    public const SUPPORTED_LOCALES = 'SUPPORTED_LOCALES';

    public const RELEASE_CHANNEL = 'AZURACAST_VERSION';

    public const SFTP_PORT = 'AZURACAST_SFTP_PORT';

    public const AUTO_ASSIGN_PORT_MIN = 'AUTO_ASSIGN_PORT_MIN';
    public const AUTO_ASSIGN_PORT_MAX = 'AUTO_ASSIGN_PORT_MAX';

    public const SYNC_SHORT_EXECUTION_TIME = 'SYNC_SHORT_EXECUTION_TIME';
    public const SYNC_LONG_EXECUTION_TIME = 'SYNC_LONG_EXECUTION_TIME';

    public const LOG_LEVEL = 'LOG_LEVEL';

    // Database and Cache Configuration Variables
    public const DB_HOST = 'MYSQL_HOST';
    public const DB_PORT = 'MYSQL_PORT';
    public const DB_NAME = 'MYSQL_DATABASE';
    public const DB_USER = 'MYSQL_USER';
    public const DB_PASSWORD = 'MYSQL_PASSWORD';

    public const REDIS_HOST = 'REDIS_HOST';
    public const REDIS_PORT = 'REDIS_PORT';
    public const REDIS_DB = 'REDIS_DB';

    // Default settings
    protected array $defaults = [
        self::APP_NAME => 'AzuraCast',
        self::APP_ENV => self::ENV_PRODUCTION,

        self::IS_DOCKER => true,
        self::IS_CLI => ('cli' === PHP_SAPI),

        self::ASSET_URL => '/static',

        self::SUPPORTED_LOCALES => [
            'en_US.UTF-8' => 'English (Default)',
            'cs_CZ.UTF-8' => 'čeština',             // Czech
            'de_DE.UTF-8' => 'Deutsch',             // German
            'es_ES.UTF-8' => 'Español',             // Spanish
            'fr_FR.UTF-8' => 'Français',            // French
            'el_GR.UTF-8' => 'ελληνικά',            // Greek
            'it_IT.UTF-8' => 'Italiano',            // Italian
            'hu_HU.UTF-8' => 'magyar',              // Hungarian
            'nl_NL.UTF-8' => 'Nederlands',          // Dutch
            'pl_PL.UTF-8' => 'Polski',              // Polish
            'pt_PT.UTF-8' => 'Português',           // Portuguese
            'pt_BR.UTF-8' => 'Português do Brasil', // Brazilian Portuguese
            'ru_RU.UTF-8' => 'Русский язык',        // Russian
            'sv_SE.UTF-8' => 'Svenska',             // Swedish
            'tr_TR.UTF-8' => 'Türkçe',              // Turkish
            'zh_CN.UTF-8' => '簡化字',               // Simplified Chinese
        ],
    ];

    public function __construct(array $elements = [])
    {
        $this->data = array_merge($this->defaults, $elements);
    }

    public function getAppEnvironment(): string
    {
        return $this->data[self::APP_ENV] ?? self::ENV_PRODUCTION;
    }

    public function isProduction(): bool
    {
        return self::ENV_PRODUCTION === $this->getAppEnvironment();
    }

    public function isTesting(): bool
    {
        return self::ENV_TESTING === $this->getAppEnvironment();
    }

    public function isDevelopment(): bool
    {
        return self::ENV_DEVELOPMENT === $this->getAppEnvironment();
    }

    public function isDocker(): bool
    {
        return (bool)($this->data[self::IS_DOCKER] ?? true);
    }

    public function isCli(): bool
    {
        return $this->data[self::IS_CLI] ?? ('cli' === PHP_SAPI);
    }

    public function getAppName(): string
    {
        return $this->data[self::APP_NAME] ?? 'Application';
    }

    public function getAssetUrl(): ?string
    {
        return $this->data[self::ASSET_URL] ?? '';
    }

    /**
     * @return string The base directory of the application, i.e. `/var/app/www` for Docker installations.
     */
    public function getBaseDirectory(): string
    {
        return $this->data[self::BASE_DIR];
    }

    /**
     * @return string The directory where temporary files are stored by the application, i.e. `/var/app/www_tmp`
     */
    public function getTempDirectory(): string
    {
        return $this->data[self::TEMP_DIR];
    }

    /**
     * @return string The directory where configuration files are stored by default.
     */
    public function getConfigDirectory(): string
    {
        return $this->data[self::CONFIG_DIR];
    }

    /**
     * @return string The directory where template/view files are stored.
     */
    public function getViewsDirectory(): string
    {
        return $this->data[self::VIEWS_DIR];
    }

    /**
     * @return string The parent directory the application is within, i.e. `/var/azuracast`.
     */
    public function getParentDirectory(): string
    {
        return dirname($this->getBaseDirectory());
    }

    /**
     * @return string The default directory where station data is stored.
     */
    public function getStationDirectory(): string
    {
        return $this->getParentDirectory() . '/stations';
    }

    public function isDockerRevisionAtLeast(int $version): bool
    {
        if (!$this->isDocker()) {
            return false;
        }

        $compareVersion = (int)($this->data[self::DOCKER_REVISION] ?? 0);
        return ($compareVersion >= $version);
    }

    public function getLang(): ?string
    {
        return $this->data[self::LANG];
    }

    /**
     * @return string[]
     */
    public function getSupportedLocales(): array
    {
        return $this->data[self::SUPPORTED_LOCALES] ?? [];
    }

    public function getReleaseChannel(): string
    {
        $channel = $this->data[self::RELEASE_CHANNEL] ?? 'latest';

        return ('stable' === $channel)
            ? Version::RELEASE_CHANNEL_STABLE
            : Version::RELEASE_CHANNEL_ROLLING;
    }

    public function getSftpPort(): int
    {
        return (int)($this->data[self::SFTP_PORT] ?? 2022);
    }

    public function getAutoAssignPortMin(): int
    {
        return (int)($this->data[self::AUTO_ASSIGN_PORT_MIN] ?? Configuration::DEFAULT_PORT_MIN);
    }

    public function getAutoAssignPortMax(): int
    {
        return (int)($this->data[self::AUTO_ASSIGN_PORT_MAX] ?? Configuration::DEFAULT_PORT_MAX);
    }

    public function getSyncShortExecutionTime(): int
    {
        return (int)($this->data[self::SYNC_SHORT_EXECUTION_TIME] ?? 600);
    }

    public function getSyncLongExecutionTime(): int
    {
        return (int)($this->data[self::SYNC_LONG_EXECUTION_TIME] ?? 1800);
    }

    public function getLogLevel(): string
    {
        if (!empty($this->data[self::LOG_LEVEL])) {
            $loggingLevel = strtolower($this->data[self::LOG_LEVEL]);

            $allowedLogLevels = [
                LogLevel::DEBUG,
                LogLevel::INFO,
                LogLevel::NOTICE,
                LogLevel::WARNING,
                LogLevel::ERROR,
                LogLevel::CRITICAL,
                LogLevel::ALERT,
                LogLevel::EMERGENCY,
            ];

            if (in_array($loggingLevel, $allowedLogLevels, true)) {
                return $loggingLevel;
            }
        }

        return $this->isProduction()
            ? LogLevel::NOTICE
            : LogLevel::INFO;
    }

    /**
     * @return mixed[]
     */
    public function getDatabaseSettings(): array
    {
        return [
            'host' => $this->data[self::DB_HOST] ?? ($this->isDocker() ? 'mariadb' : 'localhost'),
            'port' => (int)($this->data[self::DB_PORT] ?? 3306),
            'dbname' => $this->data[self::DB_NAME] ?? 'azuracast',
            'user' => $this->data[self::DB_USER] ?? 'azuracast',
            'password' => $this->data[self::DB_PASSWORD] ?? 'azur4c457',
        ];
    }

    /**
     * @return mixed[]
     */
    public function getRedisSettings(): array
    {
        return [
            'host' => $this->data[self::REDIS_HOST] ?? ($this->isDocker() ? 'redis' : 'localhost'),
            'port' => (int)($this->data[self::REDIS_PORT] ?? 6379),
            'db' => (int)($this->data[self::REDIS_DB] ?? 1),
        ];
    }
}
