<?php

namespace App;

use App\Radio\Configuration;
use App\Traits\AvailableStaticallyTrait;
use Doctrine\Common\Collections\ArrayCollection;

class Environment extends ArrayCollection
{
    use AvailableStaticallyTrait;

    // Environments
    public const ENV_DEVELOPMENT = 'development';
    public const ENV_TESTING = 'testing';
    public const ENV_PRODUCTION = 'production';

    // Core settings values
    public const APP_NAME = 'name';
    public const APP_ENV = 'APPLICATION_ENV';

    public const BASE_DIR = 'base_dir';
    public const TEMP_DIR = 'temp_dir';
    public const CONFIG_DIR = 'config_dir';
    public const VIEWS_DIR = 'views_dir';
    public const DOCTRINE_OPTIONS = 'doctrine_options';
    public const IS_DOCKER = 'is_docker';
    public const IS_CLI = 'is_cli';

    public const BASE_URL = 'BASE_URL';
    public const ASSETS_URL = 'assets_url';

    public const ENABLE_DATABASE = 'enable_database';
    public const ENABLE_REDIS = 'enable_redis';

    public const DOCKER_REVISION = 'AZURACAST_DC_REVISION';

    public const ENABLE_ADVANCED_FEATURES = 'ENABLE_ADVANCED_FEATURES';

    public const LANG = 'LANG';

    public const RELEASE_CHANNEL = 'AZURACAST_VERSION';

    public const SFTP_PORT = 'AZURACAST_SFTP_PORT';

    public const AUTO_ASSIGN_PORT_MIN = 'AUTO_ASSIGN_PORT_MIN';
    public const AUTO_ASSIGN_PORT_MAX = 'AUTO_ASSIGN_PORT_MAX';

    public const SYNC_SHORT_EXECUTION_TIME = 'SYNC_SHORT_EXECUTION_TIME';
    public const SYNC_LONG_EXECUTION_TIME = 'SYNC_LONG_EXECUTION_TIME';

    // Default settings
    protected array $defaults = [
        self::APP_NAME => 'Application',
        self::APP_ENV => self::ENV_PRODUCTION,

        self::IS_DOCKER => true,
        self::IS_CLI => ('cli' === PHP_SAPI),

        self::ASSETS_URL => '/static',

        self::ENABLE_DATABASE => true,
        self::ENABLE_REDIS => true,
    ];

    public function __construct(array $elements = [])
    {
        $elements = array_merge($this->defaults, $elements);
        parent::__construct($elements);
    }

    public function isProduction(): bool
    {
        if ($this->containsKey(self::APP_ENV)) {
            return (self::ENV_PRODUCTION === $this->get(self::APP_ENV));
        }
        return true;
    }

    public function isTesting(): bool
    {
        if ($this->containsKey(self::APP_ENV)) {
            return (self::ENV_TESTING === $this->get(self::APP_ENV));
        }
        return false;
    }

    public function isDocker(): bool
    {
        return (bool)($this->get(self::IS_DOCKER) ?? true);
    }

    public function isCli(): bool
    {
        return $this->get(self::IS_CLI) ?? ('cli' === PHP_SAPI);
    }

    public function enableDatabase(): bool
    {
        return (bool)($this->get(self::ENABLE_DATABASE) ?? true);
    }

    public function enableRedis(): bool
    {
        return (bool)($this->get(self::ENABLE_REDIS) ?? true);
    }

    /**
     * @return string The base directory of the application, i.e. `/var/app/www` for Docker installations.
     */
    public function getBaseDirectory(): string
    {
        return $this->get(self::BASE_DIR);
    }

    /**
     * @return string The directory where temporary files are stored by the application, i.e. `/var/app/www_tmp`
     */
    public function getTempDirectory(): string
    {
        return $this->get(self::TEMP_DIR);
    }

    /**
     * @return string The directory where configuration files are stored by default.
     */
    public function getConfigDirectory(): string
    {
        return $this->get(self::CONFIG_DIR);
    }

    /**
     * @return string The directory where template/view files are stored.
     */
    public function getViewsDirectory(): string
    {
        return $this->get(self::VIEWS_DIR);
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

        $compareVersion = (int)($this->get(self::DOCKER_REVISION) ?? 0);
        return ($compareVersion >= $version);
    }

    public function enableAdvancedFeatures(): bool
    {
        if (!$this->isDocker()) {
            return true;
        }

        return (bool)($this->get(self::ENABLE_ADVANCED_FEATURES) ?? true);
    }

    public function getLang(): ?string
    {
        return $this->get(self::LANG);
    }

    public function getReleaseChannel(): string
    {
        $channel = $this->get(self::RELEASE_CHANNEL) ?? 'latest';

        return ('stable' === $channel)
            ? Version::RELEASE_CHANNEL_STABLE
            : Version::RELEASE_CHANNEL_ROLLING;
    }

    public function getSftpPort(): int
    {
        return (int)($this->get(self::SFTP_PORT) ?? 2022);
    }

    public function getAutoAssignPortMin(): int
    {
        return (int)($this->get(self::AUTO_ASSIGN_PORT_MIN) ?? Configuration::DEFAULT_PORT_MIN);
    }

    public function getAutoAssignPortMax(): int
    {
        return (int)($this->get(self::AUTO_ASSIGN_PORT_MAX) ?? Configuration::DEFAULT_PORT_MAX);
    }

    public function getSyncShortExecutionTime(): int
    {
        return (int)($this->get(self::SYNC_SHORT_EXECUTION_TIME) ?? 600);
    }

    public function getSyncLongExecutionTime(): int
    {
        return (int)($this->get(self::SYNC_LONG_EXECUTION_TIME) ?? 1800);
    }
}
