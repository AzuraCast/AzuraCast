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

    public const ENABLE_ADVANCED_FEATURES = 'ENABLE_ADVANCED_FEATURES';

    public const LANG = 'LANG';
    public const SUPPORTED_LOCALES = 'SUPPORTED_LOCALES';

    public const RELEASE_CHANNEL = 'AZURACAST_VERSION';

    public const SFTP_PORT = 'AZURACAST_SFTP_PORT';

    public const AUTO_ASSIGN_PORT_MIN = 'AUTO_ASSIGN_PORT_MIN';
    public const AUTO_ASSIGN_PORT_MAX = 'AUTO_ASSIGN_PORT_MAX';

    public const SYNC_SHORT_EXECUTION_TIME = 'SYNC_SHORT_EXECUTION_TIME';
    public const SYNC_LONG_EXECUTION_TIME = 'SYNC_LONG_EXECUTION_TIME';

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

    public function getAssetUrl(): ?string
    {
        return $this->get(self::ASSET_URL) ?? '';
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

    /**
     * @return string[]
     */
    public function getSupportedLocales(): array
    {
        return $this->get(self::SUPPORTED_LOCALES) ?? [];
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
