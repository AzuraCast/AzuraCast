<?php

declare(strict_types=1);

namespace App;

use App\Enums\ApplicationEnvironment;
use App\Enums\ReleaseChannel;
use App\Installer\EnvFiles\AzuraCastEnvFile;
use App\Radio\Configuration;
use App\Traits\AvailableStaticallyTrait;
use App\Utilities\File;
use App\Utilities\Types;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Psr\Log\LogLevel;

final class Environment
{
    use AvailableStaticallyTrait;

    // Cached immutable values that are frequently used.
    private readonly string $baseDir;
    private readonly string $backendDir;
    private readonly string $parentDir;
    private readonly bool $isDocker;
    private readonly ApplicationEnvironment $appEnv;

    /** @var array<string, string|int|bool|float> */
    private readonly array $data;

    // Core settings values
    public const string APP_NAME = 'APP_NAME';
    public const string APP_ENV = 'APPLICATION_ENV';

    public const string TEMP_DIR = 'TEMP_DIR';
    public const string UPLOADS_DIR = 'UPLOADS_DIR';

    public const string IS_DOCKER = 'IS_DOCKER';
    public const string IS_CLI = 'IS_CLI';

    public const string ASSET_URL = 'ASSETS_URL';

    public const string LANG = 'LANG';

    public const string RELEASE_CHANNEL = 'AZURACAST_VERSION';

    public const string SFTP_PORT = 'AZURACAST_SFTP_PORT';

    public const string AUTO_ASSIGN_PORT_MIN = 'AUTO_ASSIGN_PORT_MIN';
    public const string AUTO_ASSIGN_PORT_MAX = 'AUTO_ASSIGN_PORT_MAX';

    public const string SYNC_SHORT_EXECUTION_TIME = 'SYNC_SHORT_EXECUTION_TIME';
    public const string SYNC_LONG_EXECUTION_TIME = 'SYNC_LONG_EXECUTION_TIME';
    public const string NOW_PLAYING_DELAY_TIME = 'NOW_PLAYING_DELAY_TIME';
    public const string NOW_PLAYING_MAX_CONCURRENT_PROCESSES = 'NOW_PLAYING_MAX_CONCURRENT_PROCESSES';

    public const string LOG_LEVEL = 'LOG_LEVEL';
    public const string SHOW_DETAILED_ERRORS = 'SHOW_DETAILED_ERRORS';

    public const string PROFILING_EXTENSION_ENABLED = 'PROFILING_EXTENSION_ENABLED';
    public const string PROFILING_EXTENSION_ALWAYS_ON = 'PROFILING_EXTENSION_ALWAYS_ON';
    public const string PROFILING_EXTENSION_HTTP_KEY = 'PROFILING_EXTENSION_HTTP_KEY';

    public const string ENABLE_WEB_UPDATER = 'ENABLE_WEB_UPDATER';

    // Database and Cache Configuration Variables
    public const string DB_HOST = 'MYSQL_HOST';
    public const string DB_PORT = 'MYSQL_PORT';
    public const string DB_NAME = 'MYSQL_DATABASE';
    public const string DB_USER = 'MYSQL_USER';
    public const string DB_PASSWORD = 'MYSQL_PASSWORD';

    public const string ENABLE_REDIS = 'ENABLE_REDIS';
    public const string REDIS_HOST = 'REDIS_HOST';
    public const string REDIS_PORT = 'REDIS_PORT';
    public const string REDIS_DB = 'REDIS_DB';

    public function __construct(array $elements = [])
    {
        $this->baseDir = dirname(__DIR__, 2);
        $this->backendDir = dirname(__DIR__);
        $this->parentDir = dirname($this->baseDir);
        $this->isDocker = file_exists($this->parentDir . '/.docker');

        if (! $this->isDocker) {
            try {
                $azuracastEnvPath = AzuraCastEnvFile::buildPathFromBase($this->baseDir);
                $azuracastEnv = AzuraCastEnvFile::fromEnvFile($azuracastEnvPath);
                $this->data = array_merge($elements, $azuracastEnv->toArray());
            } catch (Exception) {
                $this->data = $elements;
            }
        } else {
            $this->data = $elements;
        }

        $this->appEnv = ApplicationEnvironment::tryFrom(
            Types::string($this->data[self::APP_ENV] ?? null, '', true)
        ) ?? ApplicationEnvironment::default();
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function getAppEnvironmentEnum(): ApplicationEnvironment
    {
        return $this->appEnv;
    }

    /**
     * @return string The base directory of the application, i.e. `/var/app/www` for Docker installations.
     */
    public function getBaseDirectory(): string
    {
        return $this->baseDir;
    }

    /**
     * @return string The base directory for PHP application files, i.e. `/var/app/www/backend`.
     */
    public function getBackendDirectory(): string
    {
        return $this->backendDir;
    }

    /**
     * @return string The parent directory the application is within, i.e. `/var/azuracast`.
     */
    public function getParentDirectory(): string
    {
        return $this->parentDir;
    }

    public function isDocker(): bool
    {
        return $this->isDocker;
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

    public function showDetailedErrors(): bool
    {
        return Types::bool(
            $this->data[self::SHOW_DETAILED_ERRORS] ?? null,
            !$this->isProduction(),
            true
        );
    }

    public function isCli(): bool
    {
        return Types::bool(
            $this->data[self::IS_CLI] ?? null,
            ('cli' === PHP_SAPI)
        );
    }

    public function getAppName(): string
    {
        return Types::string(
            $this->data[self::APP_NAME] ?? null,
            'AzuraCast',
            true
        );
    }

    public function getAssetUrl(): string
    {
        return Types::string(
            $this->data[self::ASSET_URL] ?? null,
            '/static',
            true
        );
    }

    /**
     * @return string The directory where temporary files are stored by the application, i.e. `/var/app/www_tmp`
     */
    public function getTempDirectory(): string
    {
        return Types::string(
            $this->data[self::TEMP_DIR] ?? null,
            $this->getParentDirectory() . '/www_tmp',
            true
        );
    }

    /**
     * @return string The directory where user system-level uploads are stored.
     */
    public function getUploadsDirectory(): string
    {
        return Types::string(
            $this->data[self::UPLOADS_DIR] ?? null,
            File::getFirstExistingDirectory([
                $this->getParentDirectory() . '/storage/uploads',
                $this->getParentDirectory() . '/uploads',
            ]),
            true
        );
    }

    /**
     * @return string The default directory where station data is stored.
     */
    public function getStationDirectory(): string
    {
        return $this->getParentDirectory() . '/stations';
    }

    public function getInternalUri(): UriInterface
    {
        return new Uri('http://127.0.0.1:6010');
    }

    public function getLocalUri(): UriInterface
    {
        return new Uri('http://127.0.0.1');
    }

    public function getLang(): ?string
    {
        return Types::stringOrNull($this->data[self::LANG]);
    }

    public function getReleaseChannelEnum(): ReleaseChannel
    {
        return ReleaseChannel::tryFrom(Types::string($this->data[self::RELEASE_CHANNEL] ?? null))
            ?? ReleaseChannel::default();
    }

    public function getSftpPort(): int
    {
        return Types::int(
            $this->data[self::SFTP_PORT] ?? null,
            2022
        );
    }

    public function getAutoAssignPortMin(): int
    {
        return Types::int(
            $this->data[self::AUTO_ASSIGN_PORT_MIN] ?? null,
            Configuration::DEFAULT_PORT_MIN
        );
    }

    public function getAutoAssignPortMax(): int
    {
        return Types::int(
            $this->data[self::AUTO_ASSIGN_PORT_MAX] ?? null,
            Configuration::DEFAULT_PORT_MAX
        );
    }

    public function getSyncShortExecutionTime(): int
    {
        return Types::int(
            $this->data[self::SYNC_SHORT_EXECUTION_TIME] ?? null,
            600
        );
    }

    public function getSyncLongExecutionTime(): int
    {
        return Types::int(
            $this->data[self::SYNC_LONG_EXECUTION_TIME] ?? null,
            1800
        );
    }

    public function getNowPlayingDelayTime(): ?int
    {
        return Types::intOrNull($this->data[self::NOW_PLAYING_DELAY_TIME] ?? null);
    }

    public function getNowPlayingMaxConcurrentProcesses(): ?int
    {
        return Types::intOrNull($this->data[self::NOW_PLAYING_MAX_CONCURRENT_PROCESSES] ?? null);
    }

    /**
     * @phpstan-return LogLevel::*
     */
    public function getLogLevel(): string
    {
        $logLevelRaw = Types::stringOrNull($this->data[self::LOG_LEVEL] ?? null, true);
        if (null !== $logLevelRaw) {
            $loggingLevel = strtolower($logLevelRaw);
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
     * @return array{
     *     host: string,
     *     port: int,
     *     dbname: string,
     *     user: string,
     *     password: string,
     *     unix_socket?: string
     * }
     */
    public function getDatabaseSettings(): array
    {
        $dbSettings = [
            'host' => Types::string(
                $this->data[self::DB_HOST] ?? null,
                'localhost',
                true
            ),
            'port' => Types::int(
                $this->data[self::DB_PORT] ?? null,
                3306
            ),
            'dbname' => Types::string(
                $this->data[self::DB_NAME] ?? null,
                'azuracast',
                true
            ),
            'user' => Types::string(
                $this->data[self::DB_USER] ?? null,
                'azuracast',
                true
            ),
            'password' => Types::string(
                $this->data[self::DB_PASSWORD] ?? null,
                'azur4c457',
                true
            ),
        ];

        if ('localhost' === $dbSettings['host'] && $this->isDocker()) {
            $dbSettings['unix_socket'] = '/run/mysqld/mysqld.sock';
        }

        return $dbSettings;
    }

    public function useLocalDatabase(): bool
    {
        return 'localhost' === $this->getDatabaseSettings()['host'];
    }

    public function enableRedis(): bool
    {
        return Types::bool(
            $this->data[self::ENABLE_REDIS],
            true,
            true
        );
    }

    /**
     * @return array{
     *     host: string,
     *     port: int,
     *     db: int,
     *     socket?: string
     * }
     */
    public function getRedisSettings(): array
    {
        $redisSettings = [
            'host' => Types::string(
                $this->data[self::REDIS_HOST] ?? null,
                'localhost',
                true
            ),
            'port' => Types::int(
                $this->data[self::REDIS_PORT] ?? null,
                6379
            ),
            'db' => Types::int(
                $this->data[self::REDIS_DB] ?? null
            ),
        ];

        if ('localhost' === $redisSettings['host'] && $this->isDocker()) {
            $redisSettings['socket'] = '/run/redis/redis.sock';
        }

        return $redisSettings;
    }

    public function useLocalRedis(): bool
    {
        return $this->enableRedis() && 'localhost' === $this->getRedisSettings()['host'];
    }

    public function isProfilingExtensionEnabled(): bool
    {
        return Types::bool(
            $this->data[self::PROFILING_EXTENSION_ENABLED] ?? null,
            false,
            true
        );
    }

    public function isProfilingExtensionAlwaysOn(): bool
    {
        return Types::bool(
            $this->data[self::PROFILING_EXTENSION_ALWAYS_ON] ?? null,
            false,
            true
        );
    }

    public function getProfilingExtensionHttpKey(): string
    {
        return Types::string(
            $this->data[self::PROFILING_EXTENSION_HTTP_KEY] ?? null,
            'dev',
            true
        );
    }

    public function enableWebUpdater(): bool
    {
        if (!$this->isDocker()) {
            return false;
        }

        return Types::bool(
            $this->data[self::ENABLE_WEB_UPDATER] ?? null,
            false,
            true
        );
    }

    public static function getDefaultsForEnvironment(Environment $existingEnv): self
    {
        return new self([
            self::IS_CLI => $existingEnv->isCli(),
            self::IS_DOCKER => $existingEnv->isDocker(),
        ]);
    }
}
