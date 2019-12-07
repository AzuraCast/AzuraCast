<?php
namespace App\Service;

use App\Entity;
use App\Exception\SupervisorException;
use App\Settings;
use App\Version;
use Azura\Exception;
use Doctrine\DBAL\Exception\TableNotFoundException;
use fXmlRpc\Exception\FaultException;
use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use League\Flysystem\FileNotFoundException;
use Monolog\Logger;
use Sentry\ClientBuilder;
use Sentry\Options;
use Sentry\Severity;
use Sentry\State\Hub;
use Sentry\State\Scope;
use Throwable;

class Sentry
{
    /** @var Entity\Repository\SettingsRepository */
    protected Entity\Repository\SettingsRepository $settingsRepo;

    /** @var Settings */
    protected Settings $appSettings;

    /** @var Client */
    protected Client $httpClient;

    /** @var Version */
    protected Version $version;

    /** @var bool */
    protected bool $isEnabled = false;

    /** @var bool */
    protected bool $isInitialized = false;

    /** @var Hub */
    protected Hub $hub;

    public function __construct(
        Entity\Repository\SettingsRepository $settings_repo,
        Settings $app_settings,
        Version $version,
        Client $http_client
    ) {
        $this->settingsRepo = $settings_repo;
        $this->appSettings = $app_settings;
        $this->version = $version;
        $this->httpClient = $http_client;
    }

    /**
     * Initialize the Sentry reporting for the instance.
     */
    public function init(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->isInitialized = true;

        // Check for enabled status.
        try {
            $send_error_reports = (bool)$this->settingsRepo->getSetting(Entity\Settings::SEND_ERROR_REPORTS, false);
            if (!$send_error_reports) {
                return;
            }
        } catch (TableNotFoundException $e) {
            return;
        }

        if ($this->appSettings->isTesting()) {
            return;
        }

        $this->isEnabled = true;

        $server_uuid = $this->settingsRepo->getUniqueIdentifier();
        $options = [
            'dsn' => $this->appSettings['sentry_io']['dsn'],
            'environment' => $this->appSettings[Settings::APP_ENV],
            'server_name' => $server_uuid,
            'prefixes' => [
                $this->appSettings[Settings::BASE_DIR],
            ],
            'project_root' => $this->appSettings[Settings::BASE_DIR] . '/src',
            'error_types' => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT,
            'excluded_exceptions' => [
                FileNotFoundException::class,
                FaultException::class,
                SupervisorException::class,
            ],
        ];

        $commit_hash = $this->version->getCommitHash();
        if ($commit_hash) {
            $options['release'] = 'AzuraCast/AzuraCast@' . $commit_hash;
        }

        $options = new Options($options);
        $builder = new ClientBuilder($options);
        $builder->setHttpClient(new GuzzleAdapter($this->httpClient));

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

        $install_type = $this->appSettings->isDocker() ? 'docker' : 'traditional';
        $scope->setTag('type', $install_type);
    }

    /**
     * @return Hub
     */
    public function getHub(): Hub
    {
        $this->init();

        return $this->hub;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        $this->init();

        return $this->isEnabled;
    }

    /**
     * @param Throwable $e
     */
    public function handleException(Throwable $e): void
    {
        $this->init();

        if (!$this->isEnabled) {
            return;
        }

        // Don't send error reports for installations whose code is modified.
        if ($this->version->isInstallationModified()) {
            return;
        }

        $e_level = ($e instanceof Exception)
            ? $e->getLoggerLevel()
            : Logger::ERROR;

        if ($e_level < Logger::WARNING) {
            return;
        }

        $sentry_levels = [
            Logger::WARNING => Severity::warning(),
            Logger::ERROR => Severity::error(),
            Logger::CRITICAL => Severity::error(),
            Logger::ALERT => Severity::fatal(),
            Logger::EMERGENCY => Severity::fatal(),
        ];
        $sentry_level = $sentry_levels[$e_level];

        $this->hub->withScope(function (Scope $scope) use ($e, $sentry_level) {
            $scope->setLevel($sentry_level);
            $this->hub->captureException($e);
        });
    }
}
