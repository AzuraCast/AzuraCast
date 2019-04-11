<?php
namespace App\Service;

use App\Entity;
use App\Version;
use Azura\Settings;
use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Monolog\Logger;
use Sentry\ClientBuilder;
use Sentry\Options;
use Sentry\State\Hub;
use Sentry\State\Scope;

class Sentry
{
    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var Settings */
    protected $app_settings;

    /** @var Client */
    protected $http_client;

    /** @var Version */
    protected $version;

    /** @var bool */
    protected $is_enabled = false;

    /** @var Hub */
    protected $hub;

    public function __construct(
        Entity\Repository\SettingsRepository $settings_repo,
        Settings $app_settings,
        Version $version,
        Client $http_client
    ) {
        $this->settings_repo = $settings_repo;
        $this->app_settings = $app_settings;
        $this->version = $version;
        $this->http_client = $http_client;

        $this->init();
    }

    /**
     * Initialize the Sentry reporting for the instance.
     */
    public function init(): void
    {
        // Check for enabled status.
        try {
            $send_error_reports = (bool)$this->settings_repo->getSetting(Entity\Settings::SEND_ERROR_REPORTS, false);
            if (!$send_error_reports) {
                return;
            }
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException $e) {
            return;
        }

        $this->is_enabled = true;

        $server_uuid = $this->settings_repo->getUniqueIdentifier();
        $options = [
            'dsn'           => $this->app_settings['sentry_io']['dsn'],
            'environment'   => $this->app_settings[Settings::APP_ENV],
            'server_name'   => $server_uuid,
            'prefixes'      => [
                $this->app_settings[Settings::BASE_DIR]
            ],
            'project_root'  => $this->app_settings[Settings::BASE_DIR].'/src',
            'error_types'   => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT,
            'excluded_exceptions' => [
                \League\Flysystem\FileNotFoundException::class,
                \fXmlRpc\Exception\FaultException::class,
                \App\Exception\Supervisor::class,
            ],
        ];

        $commit_hash = $this->version->getCommitHash();
        if ($commit_hash) {
            $options['release'] = 'AzuraCast/AzuraCast@'.$commit_hash;
        }

        $options = new Options($options);
        $builder = new ClientBuilder($options);
        $builder->setHttpClient(new GuzzleAdapter($this->http_client));

        $this->hub = new Hub($builder->getClient());
        $this->hub->configureScope([$this, 'configureScope']);
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
     * @return Hub
     */
    public function getHub(): Hub
    {
        return $this->hub;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * @param \Throwable $e
     */
    public function handleException(\Throwable $e): void
    {
        if (!$this->is_enabled) {
            return;
        }

        // Don't send error reports for installations whose code is modified.
        if ($this->version->isInstallationModified()) {
            return;
        }

        $e_level = ($e instanceof \Azura\Exception)
            ? $e->getLoggerLevel()
            : Logger::ERROR;

        if ($e_level < Logger::WARNING) {
            return;
        }

        $sentry_levels = [
            Logger::WARNING     => \Sentry\Severity::warning(),
            Logger::ERROR       => \Sentry\Severity::error(),
            Logger::CRITICAL    => \Sentry\Severity::error(),
            Logger::ALERT       => \Sentry\Severity::fatal(),
            Logger::EMERGENCY   => \Sentry\Severity::fatal(),
        ];
        $sentry_level = $sentry_levels[$e_level];

        $this->hub->withScope(function(\Sentry\State\Scope $scope) use ($e, $sentry_level) {
            $scope->setLevel($sentry_level);
            $this->hub->captureException($e);
        });
    }
}
