<?php
namespace App\Service;

use App\Entity;
use App\Version;
use Azura\Settings;
use Sentry\State\Scope;

class Sentry
{
    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var Settings */
    protected $app_settings;

    /** @var Version */
    protected $version;

    /** @var bool|null */
    protected $is_enabled;

    public function __construct(
        Entity\Repository\SettingsRepository $settings_repo,
        Settings $app_settings,
        Version $version
    ) {
        $this->settings_repo = $settings_repo;
        $this->app_settings = $app_settings;
        $this->version = $version;
    }

    /**
     * Initialize the Sentry reporting for the instance.
     */
    public function init(): void
    {
        // Check for enabled status.
        if ($this->app_settings->isProduction()) {
            $this->is_enabled = false;
            return;
        }

        $this->is_enabled = true;

        $options = [
            'dsn'           => $this->app_settings['sentry_io']['dsn'],
            'environment'   => $this->app_settings[Settings::APP_ENV],
            'server_name'   => $this->settings_repo->getUniqueIdentifier(),
            'prefixes'      => [
                $this->app_settings[Settings::BASE_DIR]
            ],
            'project_root'  => $this->app_settings[Settings::BASE_DIR].'/src',
            'error_types'   => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT,
        ];

        $commit_hash = $this->version->getCommitHash();
        if ($commit_hash) {
            $options['release'] = 'AzuraCast/AzuraCast@'.$commit_hash;
        }

        \Sentry\init($options);
        \Sentry\configureScope([$this, 'configureScope']);
    }

    /**
     * Scope configuration handler for SentryIO
     *
     * @param Scope $scope
     */
    public function configureScope(Scope $scope): void
    {
        $scope->setUser([
            'ip' => null,
        ]);

        $install_type = $this->app_settings->isDocker() ? 'docker' : 'traditional';
        $scope->setTag('type', $install_type);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled ?? false;
    }
}
