<?php

declare(strict_types=1);

namespace App\Entity;

use App\Customization;
use App\Doctrine\Generator\UuidV6Generator;
use App\Entity;
use App\Event\GetSyncTasks;
use App\Service\Avatar;
use Doctrine\ORM\Mapping as ORM;
use GuzzleHttp\Psr7\Uri;
use OpenApi\Annotations as OA;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

/** @OA\Schema(type="object", schema="Settings") */
#[ORM\Entity, ORM\Table(name: 'settings')]
#[Attributes\Auditable]
class Settings implements Stringable
{
    use Entity\Traits\TruncateStrings;
    use Entity\Traits\TruncateInts;

    /** @OA\Property() */
    #[ORM\Column(type: 'guid', unique: true)]
    #[ORM\Id, ORM\GeneratedValue(strategy: 'CUSTOM'), ORM\CustomIdGenerator(UuidV6Generator::class)]
    protected string $app_unique_identifier;

    public function getAppUniqueIdentifier(): string
    {
        if (!isset($this->app_unique_identifier)) {
            throw new \RuntimeException('Application Unique ID not generated yet.');
        }

        return $this->app_unique_identifier;
    }

    /**
     * @OA\Property(
     *     description="Site Base URL",
     *     example="https://your.azuracast.site"
     * )
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $base_url = '';

    public function getBaseUrl(): ?string
    {
        return $this->base_url;
    }

    public function setBaseUrl(?string $baseUrl): void
    {
        if (null === $baseUrl) {
            $this->base_url = null;
            return;
        }

        // Filter the base URL to avoid trailing slashes and other problems.
        $baseUri = new Uri($baseUrl);
        if ('' === $baseUri->getScheme()) {
            $baseUri = $baseUri->withScheme('http');
        }
        if ('/' === $baseUri->getPath()) {
            $baseUri = $baseUri->withPath('');
        }
        if (Uri::isDefaultPort($baseUri)) {
            $baseUri = $baseUri->withPort(null);
        }

        $this->base_url = $this->truncateNullableString((string)$baseUri);
    }

    /**
     * @OA\Property(
     *     description="AzuraCast Instance Name",
     *     example="My AzuraCast Instance"
     * )
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $instance_name = null;

    public function getInstanceName(): ?string
    {
        return $this->instance_name;
    }

    public function setInstanceName(?string $instanceName): void
    {
        $this->instance_name = $this->truncateNullableString($instanceName);
    }

    /**
     * @OA\Property(
     *     description="Prefer Browser URL (If Available)",
     *     example="false"
     * )
     */
    #[ORM\Column]
    protected bool $prefer_browser_url = false;

    public function getPreferBrowserUrl(): bool
    {
        return $this->prefer_browser_url;
    }

    public function setPreferBrowserUrl(bool $preferBrowserUrl): void
    {
        $this->prefer_browser_url = $preferBrowserUrl;
    }

    /**
     * @OA\Property(
     *     description="Use Web Proxy for Radio",
     *     example="false"
     * )
     */
    #[ORM\Column]
    protected bool $use_radio_proxy = false;

    public function getUseRadioProxy(): bool
    {
        return $this->use_radio_proxy;
    }

    public function setUseRadioProxy(bool $useRadioProxy): void
    {
        $this->use_radio_proxy = $useRadioProxy;
    }

    /**
     * @OA\Property(
     *     description="Days of Playback History to Keep"
     * )
     */
    #[ORM\Column(type: 'smallint')]
    #[Assert\Choice([0, 14, 30, 60, 365, 730])]
    protected int $history_keep_days = Entity\SongHistory::DEFAULT_DAYS_TO_KEEP;

    public function getHistoryKeepDays(): int
    {
        return $this->history_keep_days;
    }

    public function setHistoryKeepDays(int $historyKeepDays): void
    {
        $this->history_keep_days = $this->truncateSmallInt($historyKeepDays);
    }

    /**
     * @OA\Property(
     *     description="Always Use HTTPS",
     *     example="false"
     * )
     */
    #[ORM\Column]
    protected bool $always_use_ssl = false;

    public function getAlwaysUseSsl(): bool
    {
        return $this->always_use_ssl;
    }

    public function setAlwaysUseSsl(bool $alwaysUseSsl): void
    {
        $this->always_use_ssl = $alwaysUseSsl;
    }

    /**
     * @OA\Property(
     *     description="API 'Access-Control-Allow-Origin' header",
     *     example="*"
     * )
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $api_access_control = '';

    public function getApiAccessControl(): string
    {
        return $this->api_access_control ?? '';
    }

    public function setApiAccessControl(?string $apiAccessControl): void
    {
        $this->api_access_control = $this->truncateNullableString($apiAccessControl);
    }

    /**
     * @OA\Property(
     *     description="Whether to use Websockets for Now Playing data updates.",
     *     example="false"
     * )
     */
    #[ORM\Column]
    protected bool $enable_websockets = false;

    public function getEnableWebsockets(): bool
    {
        return $this->enable_websockets;
    }

    public function setEnableWebsockets(bool $enableWebsockets): void
    {
        $this->enable_websockets = $enableWebsockets;
    }

    /**
     * @OA\Property(
     *     description="Listener Analytics Collection"
     * )
     */
    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice([Analytics::LEVEL_NONE, Analytics::LEVEL_NO_IP, Analytics::LEVEL_ALL])]
    protected ?string $analytics = Analytics::LEVEL_ALL;

    public function getAnalytics(): string
    {
        return $this->analytics ?? Analytics::LEVEL_ALL;
    }

    public function setAnalytics(?string $analytics): void
    {
        $this->analytics = $this->truncateNullableString($analytics, 50);
    }

    /**
     * @OA\Property(
     *     description="Check for Updates and Announcements",
     *     example="true"
     * )
     */
    #[ORM\Column]
    protected bool $check_for_updates = true;

    public function getCheckForUpdates(): bool
    {
        return $this->check_for_updates;
    }

    public function setCheckForUpdates(bool $checkForUpdates): void
    {
        $this->check_for_updates = $checkForUpdates;
    }

    /**
     * @OA\Property(
     *     description="Results of the latest update check.",
     *     example=""
     * )
     * @var mixed[]|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Attributes\AuditIgnore]
    protected ?array $update_results = null;

    /**
     * @return mixed[]|null
     */
    public function getUpdateResults(): ?array
    {
        return $this->update_results;
    }

    public function setUpdateResults(?array $updateResults): void
    {
        $this->update_results = $updateResults;
    }

    /**
     * @OA\Property(
     *     description="The UNIX timestamp when updates were last checked.",
     *     example=SAMPLE_TIMESTAMP
     * )
     */
    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $update_last_run = 0;

    public function getUpdateLastRun(): int
    {
        return $this->update_last_run;
    }

    public function setUpdateLastRun(int $updateLastRun): void
    {
        $this->update_last_run = $updateLastRun;
    }

    public function updateUpdateLastRun(): void
    {
        $this->setUpdateLastRun(time());
    }

    /**
     * @OA\Property(
     *     description="Base Theme for Public Pages",
     *     example="light"
     * )
     */
    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice([Customization::THEME_BROWSER, Customization::THEME_LIGHT, Customization::THEME_DARK])]
    protected ?string $public_theme = Customization::DEFAULT_THEME;

    public function getPublicTheme(): string
    {
        return $this->public_theme ?? Customization::DEFAULT_THEME;
    }

    public function setPublicTheme(?string $publicTheme): void
    {
        $this->public_theme = $publicTheme;
    }

    /**
     * @OA\Property(
     *     description="Hide Album Art on Public Pages",
     *     example="false"
     * )
     */
    #[ORM\Column]
    protected bool $hide_album_art = false;

    public function getHideAlbumArt(): bool
    {
        return $this->hide_album_art;
    }

    public function setHideAlbumArt(bool $hideAlbumArt): void
    {
        $this->hide_album_art = $hideAlbumArt;
    }

    /**
     * @OA\Property(
     *     description="Homepage Redirect URL",
     *     example="https://example.com/"
     * )
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $homepage_redirect_url = null;

    public function getHomepageRedirectUrl(): ?string
    {
        return $this->homepage_redirect_url;
    }

    public function setHomepageRedirectUrl(?string $homepageRedirectUrl): void
    {
        $this->homepage_redirect_url = $this->truncateNullableString($homepageRedirectUrl);
    }

    /**
     * @OA\Property(
     *     description="Default Album Art URL",
     *     example="https://example.com/image.jpg"
     * )
     */
    #[ORM\Column(nullable: true)]
    protected ?string $default_album_art_url = null;

    public function getDefaultAlbumArtUrl(): ?string
    {
        return $this->default_album_art_url;
    }

    public function setDefaultAlbumArtUrl(?string $defaultAlbumArtUrl): void
    {
        $this->default_album_art_url = $this->truncateNullableString($defaultAlbumArtUrl);
    }

    /**
     * @OA\Property(
     *     description="Attempt to fetch album art from external sources when processing media.",
     *     example="false"
     * )
     */
    #[ORM\Column]
    protected bool $use_external_album_art_when_processing_media = false;

    public function getUseExternalAlbumArtWhenProcessingMedia(): bool
    {
        return $this->use_external_album_art_when_processing_media;
    }

    public function setUseExternalAlbumArtWhenProcessingMedia(bool $useExternalAlbumArtWhenProcessingMedia): void
    {
        $this->use_external_album_art_when_processing_media = $useExternalAlbumArtWhenProcessingMedia;
    }

    /**
     * @OA\Property(
     *     description="Attempt to fetch album art from external sources in API requests.",
     *     example="false"
     * )
     */
    #[ORM\Column]
    protected bool $use_external_album_art_in_apis = false;

    public function getUseExternalAlbumArtInApis(): bool
    {
        return $this->use_external_album_art_in_apis;
    }

    public function setUseExternalAlbumArtInApis(bool $useExternalAlbumArtInApis): void
    {
        $this->use_external_album_art_in_apis = $useExternalAlbumArtInApis;
    }

    /**
     * @OA\Property(
     *     description="An API key to connect to Last.fm services, if provided.",
     *     example="SAMPLE-API-KEY"
     * )
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $last_fm_api_key = null;

    public function getLastFmApiKey(): ?string
    {
        return $this->last_fm_api_key;
    }

    public function setLastFmApiKey(?string $lastFmApiKey): void
    {
        $lastFmApiKey = trim($lastFmApiKey ?? '');
        $lastFmApiKey = (!empty($lastFmApiKey)) ? $lastFmApiKey : null;

        $this->last_fm_api_key = $this->truncateNullableString($lastFmApiKey);
    }

    /**
     * @OA\Property(
     *     description="Hide AzuraCast Branding on Public Pages",
     *     example="false"
     * )
     */
    #[ORM\Column]
    protected bool $hide_product_name = false;

    public function getHideProductName(): bool
    {
        return $this->hide_product_name;
    }

    public function setHideProductName(bool $hideProductName): void
    {
        $this->hide_product_name = $hideProductName;
    }

    /**
     * @OA\Property(
     *     description="Custom CSS for Public Pages",
     *     example=""
     * )
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $public_custom_css = null;

    public function getPublicCustomCss(): ?string
    {
        return $this->public_custom_css;
    }

    public function setPublicCustomCss(?string $publicCustomCss): void
    {
        $this->public_custom_css = $publicCustomCss;
    }

    /**
     * @OA\Property(
     *     description="Custom JS for Public Pages",
     *     example=""
     * )
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $public_custom_js = null;

    public function getPublicCustomJs(): ?string
    {
        return $this->public_custom_js;
    }

    public function setPublicCustomJs(?string $publicCustomJs): void
    {
        $this->public_custom_js = $publicCustomJs;
    }

    /**
     * @OA\Property(
     *     description="Custom CSS for Internal Pages",
     *     example=""
     * )
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $internal_custom_css = null;

    public function getInternalCustomCss(): ?string
    {
        return $this->internal_custom_css;
    }

    public function setInternalCustomCss(?string $internalCustomCss): void
    {
        $this->internal_custom_css = $internalCustomCss;
    }

    /**
     * @OA\Property(
     *     description="Whether backup is enabled.",
     *     example="false"
     * )
     */
    #[ORM\Column]
    protected bool $backup_enabled = false;

    public function getBackupEnabled(): bool
    {
        return $this->backup_enabled;
    }

    public function setBackupEnabled(bool $backupEnabled): void
    {
        $this->backup_enabled = $backupEnabled;
    }

    /**
     * @OA\Property(
     *     description="The timecode (i.e. 400 for 4:00AM) when automated backups should run.",
     *     example=400
     * )
     */
    #[ORM\Column(length: 4, nullable: true)]
    protected ?string $backup_time_code = null;

    public function getBackupTimeCode(): ?string
    {
        return $this->backup_time_code;
    }

    public function setBackupTimeCode(?string $backupTimeCode): void
    {
        $this->backup_time_code = $backupTimeCode;
    }

    /**
     * @OA\Property(
     *     description="Whether to exclude media in automated backups.",
     *     example="false"
     * )
     */
    #[ORM\Column]
    protected bool $backup_exclude_media = false;

    public function getBackupExcludeMedia(): bool
    {
        return $this->backup_exclude_media;
    }

    public function setBackupExcludeMedia(bool $backupExcludeMedia): void
    {
        $this->backup_exclude_media = $backupExcludeMedia;
    }

    /**
     * @OA\Property(
     *     description="Number of backups to keep, or infinite if zero/null.",
     *     example=2
     * )
     */
    #[ORM\Column(type: 'smallint')]
    protected int $backup_keep_copies = 0;

    public function getBackupKeepCopies(): int
    {
        return $this->backup_keep_copies;
    }

    public function setBackupKeepCopies(int $backupKeepCopies): void
    {
        $this->backup_keep_copies = $this->truncateSmallInt($backupKeepCopies);
    }

    /**
     * @OA\Property(
     *     description="The storage location ID for automated backups.",
     *     example=1
     * )
     */
    #[ORM\Column(nullable: true)]
    protected ?int $backup_storage_location = null;

    public function getBackupStorageLocation(): ?int
    {
        return $this->backup_storage_location;
    }

    public function setBackupStorageLocation(?int $backupStorageLocation): void
    {
        $this->backup_storage_location = $backupStorageLocation;
    }

    /**
     * @OA\Property(
     *     description="The UNIX timestamp when automated backup was last run.",
     *     example=SAMPLE_TIMESTAMP
     * )
     */
    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $backup_last_run = 0;

    public function getBackupLastRun(): int
    {
        return $this->backup_last_run;
    }

    public function setBackupLastRun(int $backupLastRun): void
    {
        $this->backup_last_run = $backupLastRun;
    }

    public function updateBackupLastRun(): void
    {
        $this->setBackupLastRun(time());
    }

    /**
     * @OA\Property(
     *     description="The result of the latest automated backup task.",
     *     example=""
     * )
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Attributes\AuditIgnore]
    protected ?string $backup_last_result = null;

    public function getBackupLastResult(): ?string
    {
        return $this->backup_last_result;
    }

    public function setBackupLastResult(?string $backupLastResult): void
    {
        $this->backup_last_result = $backupLastResult;
    }

    /**
     * @OA\Property(
     *     description="The output of the latest automated backup task.",
     *     example=""
     * )
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Attributes\AuditIgnore]
    protected ?string $backup_last_output = null;

    public function getBackupLastOutput(): ?string
    {
        return $this->backup_last_output;
    }

    public function setBackupLastOutput(?string $backupLastOutput): void
    {
        $this->backup_last_output = $backupLastOutput;
    }

    /**
     * @OA\Property(
     *     description="The UNIX timestamp when setup was last completed.",
     *     example=SAMPLE_TIMESTAMP
     * )
     */
    #[ORM\Column]
    protected int $setup_complete_time = 0;

    public function getSetupCompleteTime(): int
    {
        return $this->setup_complete_time;
    }

    public function isSetupComplete(): bool
    {
        return (0 !== $this->setup_complete_time);
    }

    public function setSetupCompleteTime(int $setupCompleteTime): void
    {
        $this->setup_complete_time = $setupCompleteTime;
    }

    public function updateSetupComplete(): void
    {
        $this->setSetupCompleteTime(time());
    }

    /**
     * @OA\Property(
     *     description="The current cached now playing data.",
     *     example=""
     * )
     * @var mixed[]|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Attributes\AuditIgnore]
    protected ?array $nowplaying = null;

    /**
     * @return mixed[]|null
     */
    public function getNowplaying(): ?array
    {
        return $this->nowplaying;
    }

    public function setNowplaying(?array $nowplaying): void
    {
        $this->nowplaying = $nowplaying;
    }

    /**
     * @OA\Property(
     *     description="The UNIX timestamp when the now playing sync task was last run.",
     *     example=SAMPLE_TIMESTAMP
     * )
     */
    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $sync_nowplaying_last_run = 0;

    public function getSyncNowplayingLastRun(): int
    {
        return $this->sync_nowplaying_last_run;
    }

    public function setSyncNowplayingLastRun(int $syncNowplayingLastRun): void
    {
        $this->sync_nowplaying_last_run = $syncNowplayingLastRun;
    }

    /**
     * @OA\Property(
     *     description="The UNIX timestamp when the 60-second 'short' sync task was last run.",
     *     example=SAMPLE_TIMESTAMP
     * )
     */
    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $sync_short_last_run = 0;

    public function getSyncShortLastRun(): int
    {
        return $this->sync_short_last_run;
    }

    public function setSyncShortLastRun(int $syncShortLastRun): void
    {
        $this->sync_short_last_run = $syncShortLastRun;
    }

    /**
     * @OA\Property(
     *     description="The UNIX timestamp when the 5-minute 'medium' sync task was last run.",
     *     example=SAMPLE_TIMESTAMP
     * )
     */
    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $sync_medium_last_run = 0;

    public function getSyncMediumLastRun(): int
    {
        return $this->sync_medium_last_run;
    }

    public function setSyncMediumLastRun(int $syncMediumLastRun): void
    {
        $this->sync_medium_last_run = $syncMediumLastRun;
    }

    /**
     * @OA\Property(
     *     description="The UNIX timestamp when the 1-hour 'long' sync task was last run.",
     *     example=SAMPLE_TIMESTAMP
     * )
     */
    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $sync_long_last_run = 0;

    public function getSyncLongLastRun(): int
    {
        return $this->sync_long_last_run;
    }

    public function setSyncLongLastRun(int $syncLongLastRun): void
    {
        $this->sync_long_last_run = $syncLongLastRun;
    }

    public function getSyncLastRunTime(string $type): int
    {
        $timesByType = [
            GetSyncTasks::SYNC_NOWPLAYING => $this->sync_nowplaying_last_run,
            GetSyncTasks::SYNC_SHORT => $this->sync_short_last_run,
            GetSyncTasks::SYNC_MEDIUM => $this->sync_medium_last_run,
            GetSyncTasks::SYNC_LONG => $this->sync_long_last_run,
        ];

        return $timesByType[$type] ?? 0;
    }

    public function updateSyncLastRunTime(string $type): void
    {
        switch ($type) {
            case GetSyncTasks::SYNC_NOWPLAYING:
                $this->sync_nowplaying_last_run = time();
                break;

            case GetSyncTasks::SYNC_SHORT:
                $this->sync_short_last_run = time();
                break;

            case GetSyncTasks::SYNC_MEDIUM:
                $this->sync_medium_last_run = time();
                break;

            case GetSyncTasks::SYNC_LONG:
                $this->sync_long_last_run = time();
                break;
        }
    }

    /**
     * @OA\Property(
     *     description="This installation's external IP.",
     *     example="192.168.1.1"
     * )
     */
    #[ORM\Column(length: 45, nullable: true)]
    #[Attributes\AuditIgnore]
    protected ?string $external_ip = null;

    public function getExternalIp(): ?string
    {
        return $this->external_ip;
    }

    public function setExternalIp(?string $externalIp): void
    {
        $this->external_ip = $externalIp;
    }

    /**
     * @OA\Property(
     *     description="The license key for the Maxmind Geolite download.",
     *     example=""
     * )
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $geolite_license_key = null;

    public function getGeoliteLicenseKey(): ?string
    {
        return (null === $this->geolite_license_key)
            ? null
            : trim($this->geolite_license_key);
    }

    public function setGeoliteLicenseKey(?string $geoliteLicenseKey): void
    {
        $this->geolite_license_key = $geoliteLicenseKey;
    }

    /**
     * @OA\Property(
     *     description="The UNIX timestamp when the Maxmind Geolite was last downloaded.",
     *     example=SAMPLE_TIMESTAMP
     * )
     */
    #[ORM\Column]
    #[Attributes\AuditIgnore]
    protected int $geolite_last_run = 0;

    public function getGeoliteLastRun(): int
    {
        return $this->geolite_last_run;
    }

    public function setGeoliteLastRun(int $geoliteLastRun): void
    {
        $this->geolite_last_run = $geoliteLastRun;
    }

    public function updateGeoliteLastRun(): void
    {
        $this->setGeoliteLastRun(time());
    }

    /**
     * @OA\Property(
     *     description="Whether to enable 'advanced' functionality in the system that is intended for power users.",
     *     example=false
     * )
     */
    #[ORM\Column]
    protected bool $enable_advanced_features = false;

    public function getEnableAdvancedFeatures(): bool
    {
        return $this->enable_advanced_features;
    }

    public function setEnableAdvancedFeatures(bool $enableAdvancedFeatures): void
    {
        $this->enable_advanced_features = $enableAdvancedFeatures;
    }

    /**
     * @OA\Property(
     *     description="Enable e-mail delivery across the application.",
     *     example="true"
     * )
     */
    #[ORM\Column]
    protected bool $mail_enabled = false;

    public function getMailEnabled(): bool
    {
        return $this->mail_enabled;
    }

    public function setMailEnabled(bool $mailEnabled): void
    {
        $this->mail_enabled = $mailEnabled;
    }

    /**
     * @OA\Property(
     *     description="The name of the sender of system e-mails.",
     *     example="AzuraCast"
     * )
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $mail_sender_name = '';

    public function getMailSenderName(): string
    {
        return $this->mail_sender_name ?? '';
    }

    public function setMailSenderName(?string $mailSenderName): void
    {
        $this->mail_sender_name = $mailSenderName;
    }

    /**
     * @OA\Property(
     *     description="The e-mail address of the sender of system e-mails.",
     *     example="example@example.com"
     * )
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $mail_sender_email = '';

    public function getMailSenderEmail(): string
    {
        return $this->mail_sender_email ?? '';
    }

    public function setMailSenderEmail(?string $mailSenderEmail): void
    {
        $this->mail_sender_email = $mailSenderEmail;
    }

    /**
     * @OA\Property(
     *     description="The host to send outbound SMTP mail.",
     *     example="smtp.example.com"
     * )
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $mail_smtp_host = '';

    public function getMailSmtpHost(): string
    {
        return $this->mail_smtp_host ?? '';
    }

    public function setMailSmtpHost(?string $mailSmtpHost): void
    {
        $this->mail_smtp_host = $mailSmtpHost;
    }

    /**
     * @OA\Property(
     *     description="The port for sending outbound SMTP mail.",
     *     example=465
     * )
     */
    #[ORM\Column(type: 'smallint')]
    protected int $mail_smtp_port = 0;

    public function getMailSmtpPort(): int
    {
        return $this->mail_smtp_port;
    }

    public function setMailSmtpPort(int $mailSmtpPort): void
    {
        $this->mail_smtp_port = $this->truncateSmallInt($mailSmtpPort);
    }

    /**
     * @OA\Property(
     *     description="The username when connecting to SMTP mail.",
     *     example="username"
     * )
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $mail_smtp_username = '';

    public function getMailSmtpUsername(): string
    {
        return $this->mail_smtp_username ?? '';
    }

    public function setMailSmtpUsername(?string $mailSmtpUsername): void
    {
        $this->mail_smtp_username = $this->truncateNullableString($mailSmtpUsername);
    }

    /**
     * @OA\Property(
     *     description="The password when connecting to SMTP mail.",
     *     example="password"
     * )
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $mail_smtp_password = '';

    public function getMailSmtpPassword(): string
    {
        return $this->mail_smtp_password ?? '';
    }

    public function setMailSmtpPassword(?string $mailSmtpPassword): void
    {
        $this->mail_smtp_password = $mailSmtpPassword;
    }

    /**
     * @OA\Property(
     *     description="Whether to use a secure (TLS) connection when sending SMTP mail.",
     *     example="true"
     * )
     */
    #[ORM\Column]
    protected bool $mail_smtp_secure = true;

    public function getMailSmtpSecure(): bool
    {
        return $this->mail_smtp_secure;
    }

    public function setMailSmtpSecure(bool $mailSmtpSecure): void
    {
        $this->mail_smtp_secure = $mailSmtpSecure;
    }

    /**
     * @OA\Property(
     *     description="The external avatar service to use when fetching avatars.",
     *     example="libravatar"
     * )
     */
    #[ORM\Column(length: 25, nullable: true)]
    protected ?string $avatar_service = null;

    public function getAvatarService(): string
    {
        return $this->avatar_service ?? Avatar::DEFAULT_SERVICE;
    }

    public function setAvatarService(?string $avatarService): void
    {
        $this->avatar_service = $this->truncateNullableString($avatarService, 25);
    }

    /**
     * @OA\Property(
     *     description="The default avatar URL.",
     *     example=""
     * )
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $avatar_default_url = null;

    public function getAvatarDefaultUrl(): string
    {
        return $this->avatar_default_url ?? Avatar::DEFAULT_AVATAR;
    }

    public function setAvatarDefaultUrl(?string $avatarDefaultUrl): void
    {
        $this->avatar_default_url = $avatarDefaultUrl;
    }

    public function __toString(): string
    {
        return 'Settings';
    }
}
