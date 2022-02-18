<?php

declare(strict_types=1);

namespace App;

use App\Enums\ApplicationEnvironment;
use App\Enums\ReleaseChannel;
use App\Radio\Configuration;
use App\Traits\AvailableStaticallyTrait;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Psr\Log\LogLevel;

class Environment
{
    use AvailableStaticallyTrait;

    protected array $data = [];

    // Core settings values
    public const APP_NAME = 'APP_NAME';
    public const APP_ENV = 'APPLICATION_ENV';

    public const BASE_DIR = 'BASE_DIR';
    public const TEMP_DIR = 'TEMP_DIR';
    public const CONFIG_DIR = 'CONFIG_DIR';
    public const VIEWS_DIR = 'VIEWS_DIR';
    public const UPLOADS_DIR = 'UPLOADS_DIR';

    public const IS_DOCKER = 'IS_DOCKER';
    public const IS_CLI = 'IS_CLI';

    public const ASSET_URL = 'ASSETS_URL';

    public const DOCKER_REVISION = 'AZURACAST_DC_REVISION';

    public const LANG = 'LANG';

    public const RELEASE_CHANNEL = 'AZURACAST_VERSION';

    public const SFTP_PORT = 'AZURACAST_SFTP_PORT';

    public const AUTO_ASSIGN_PORT_MIN = 'AUTO_ASSIGN_PORT_MIN';
    public const AUTO_ASSIGN_PORT_MAX = 'AUTO_ASSIGN_PORT_MAX';

    public const SYNC_SHORT_EXECUTION_TIME = 'SYNC_SHORT_EXECUTION_TIME';
    public const SYNC_LONG_EXECUTION_TIME = 'SYNC_LONG_EXECUTION_TIME';

    public const LOG_LEVEL = 'LOG_LEVEL';

    public const PROFILING_EXTENSION_ENABLED = 'PROFILING_EXTENSION_ENABLED';
    public const PROFILING_EXTENSION_ALWAYS_ON = 'PROFILING_EXTENSION_ALWAYS_ON';
    public const PROFILING_EXTENSION_HTTP_KEY = 'PROFILING_EXTENSION_HTTP_KEY';

    // Database and Cache Configuration Variables
    public const DB_HOST = 'MYSQL_HOST';
    public const DB_PORT = 'MYSQL_PORT';
    public const DB_NAME = 'MYSQL_DATABASE';
    public const DB_USER = 'MYSQL_USER';
    public const DB_PASSWORD = 'MYSQL_PASSWORD';

    public const ENABLE_REDIS = 'ENABLE_REDIS';
    public const REDIS_HOST = 'REDIS_HOST';
    public const REDIS_PORT = 'REDIS_PORT';
    public const REDIS_DB = 'REDIS_DB';

    // Default settings
    protected array $defaults = [
        self::APP_NAME => 'AzuraCast',

        self::LOG_LEVEL => LogLevel::NOTICE,
        self::IS_DOCKER => true,
        self::IS_CLI    => ('cli' === PHP_SAPI),

        self::ASSET_URL => '/static',

        self::AUTO_ASSIGN_PORT_MIN => 8000,
        self::AUTO_ASSIGN_PORT_MAX => 8499,

        self::ENABLE_REDIS => true,

        self::SYNC_SHORT_EXECUTION_TIME => 600,
        self::SYNC_LONG_EXECUTION_TIME  => 1800,

        self::PROFILING_EXTENSION_ENABLED   => 0,
        self::PROFILING_EXTENSION_ALWAYS_ON => 0,
        self::PROFILING_EXTENSION_HTTP_KEY  => 'dev',
    ];

    public function __construct(array $elements = [])
    {
        $this->data = array_merge($this->defaults, $elements);
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function getAppEnvironmentEnum(): ApplicationEnvironment
    {
        return ApplicationEnvironment::tryFrom($this->data[self::APP_ENV] ?? '')
            ?? ApplicationEnvironment::default();
    }

    public function isProduction(): bool
    {
        return ApplicationEnvironment::Production === $this->getAppEnvironmentEnum();
    }

    public function isTesting(): bool
    {
        return ApplicationEnvironment::Testing === $this->getAppEnvironmentEnum();
    }

    public function isDevelopment(): bool
    {
        return ApplicationEnvironment::Development === $this->getAppEnvironmentEnum();
    }

    public function isDocker(): bool
    {
        return self::envToBool($this->data[self::IS_DOCKER] ?? true);
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
     * @return string The directory where user system-level uploads are stored.
     */
    public function getUploadsDirectory(): string
    {
        return $this->data[self::UPLOADS_DIR];
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

    public function getUriToWeb(): UriInterface
    {
        if ($this->isDocker()) {
            return $this->isDockerRevisionAtLeast(5)
                ? new Uri('http://web')
                : new Uri('http://nginx');
        }

        return new Uri('http://127.0.0.1');
    }

    public function getUriToStations(): UriInterface
    {
        return $this->isDocker()
            ? new Uri('http://stations')
            : new Uri('http://127.0.0.1');
    }

    public function getLang(): ?string
    {
        return $this->data[self::LANG];
    }

    public function getReleaseChannelEnum(): ReleaseChannel
    {
        return ReleaseChannel::tryFrom($this->data[self::RELEASE_CHANNEL] ?? '')
            ?? ReleaseChannel::default();
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

    /**
     * @phpstan-return LogLevel::*
     */
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
            'host'     => $this->data[self::DB_HOST] ?? ($this->isDocker() ? 'mariadb' : 'localhost'),
            'port'     => (int)($this->data[self::DB_PORT] ?? 3306),
            'dbname'   => $this->data[self::DB_NAME] ?? 'azuracast',
            'user'     => $this->data[self::DB_USER] ?? 'azuracast',
            'password' => $this->data[self::DB_PASSWORD] ?? 'azur4c457',
        ];
    }

    public function enableRedis(): bool
    {
        return self::envToBool($this->data[self::ENABLE_REDIS] ?? true);
    }

    /**
     * @return mixed[]
     */
    public function getRedisSettings(): array
    {
        return [
            'host' => $this->data[self::REDIS_HOST] ?? ($this->isDocker() ? 'redis' : 'localhost'),
            'port' => (int)($this->data[self::REDIS_PORT] ?? 6379),
            'db'   => (int)($this->data[self::REDIS_DB] ?? 1),
        ];
    }

    public function isProfilingExtensionEnabled(): bool
    {
        return self::envToBool($this->data[self::PROFILING_EXTENSION_ENABLED] ?? false);
    }

    public function isProfilingExtensionAlwaysOn(): bool
    {
        return self::envToBool($this->data[self::PROFILING_EXTENSION_ALWAYS_ON] ?? false);
    }

    public function getProfilingExtensionHttpKey(): string
    {
        return $this->data[self::PROFILING_EXTENSION_HTTP_KEY] ?? 'dev';
    }

    public static function envToBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return 0 !== $value;
        }
        if (null === $value) {
            return false;
        }

        $value = (string)$value;
        return str_starts_with(strtolower($value), 'y')
            || 'true' === strtolower($value)
            || '1' === $value;
    }
}
