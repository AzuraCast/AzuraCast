<?php

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Customization;
use App\Entity;
use App\Event\GetSyncTasks;
use App\Service\Avatar;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="settings")
 * @ORM\Entity()
 *
 * @OA\Schema(type="object", schema="Settings")
 * @AuditLog\Auditable
 */
class Settings
{
    use Entity\Traits\TruncateStrings;
    use Entity\Traits\TruncateInts;

    /**
     * @ORM\Id
     * @ORM\Column(name="app_unique_identifier", type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     *
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @OA\Property(example="")
     * @var UuidInterface The unique identifier for this installation (for update checks).
     */
    protected $app_unique_identifier;

    public function getAppUniqueIdentifier(): UuidInterface
    {
        return $this->app_unique_identifier;
    }

    /**
     * @ORM\Column(name="base_url", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="https://your.azuracast.site")
     *
     * @var string|null Site Base URL
     */
    protected $base_url = '';

    public function getBaseUrl(): ?string
    {
        return $this->base_url;
    }

    public function setBaseUrl(?string $baseUrl): void
    {
        $this->base_url = $this->truncateString($baseUrl);
    }

    /**
     * @ORM\Column(name="instance_name", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="My AzuraCast Instance")
     * @var string|null AzuraCast Instance Name
     */
    protected $instance_name = null;

    public function getInstanceName(): ?string
    {
        return $this->instance_name;
    }

    public function setInstanceName(?string $instanceName): void
    {
        $this->instance_name = $this->truncateString($instanceName);
    }

    /**
     * @ORM\Column(name="prefer_browser_url", type="boolean")
     * @OA\Property(example="false")
     * @var bool Prefer Browser URL (If Available)
     */
    protected $prefer_browser_url = false;

    public function getPreferBrowserUrl(): bool
    {
        return $this->prefer_browser_url;
    }

    public function setPreferBrowserUrl(bool $preferBrowserUrl): void
    {
        $this->prefer_browser_url = $preferBrowserUrl;
    }

    /**
     * @ORM\Column(name="use_radio_proxy", type="boolean")
     * @OA\Property(example="false")
     * @var bool Use Web Proxy for Radio
     */
    protected $use_radio_proxy = false;

    public function getUseRadioProxy(): bool
    {
        return $this->use_radio_proxy;
    }

    public function setUseRadioProxy(bool $useRadioProxy): void
    {
        $this->use_radio_proxy = $useRadioProxy;
    }

    /**
     * @ORM\Column(name="history_keep_days", type="smallint")
     *
     * @OA\Property()
     * @Assert\Choice({0,14,30,60,365,730})
     * @var int Days of Playback History to Keep
     */
    protected $history_keep_days = Entity\SongHistory::DEFAULT_DAYS_TO_KEEP;

    public function getHistoryKeepDays(): int
    {
        return $this->history_keep_days;
    }

    public function setHistoryKeepDays(int $historyKeepDays): void
    {
        $this->history_keep_days = $this->truncateSmallInt($historyKeepDays);
    }

    /**
     * @ORM\Column(name="always_use_ssl", type="boolean")
     *
     * @OA\Property(example="false")
     * @var bool Always Use HTTPS
     */
    protected $always_use_ssl = false;

    public function getAlwaysUseSsl(): bool
    {
        return $this->always_use_ssl;
    }

    public function setAlwaysUseSsl(bool $alwaysUseSsl): void
    {
        $this->always_use_ssl = $alwaysUseSsl;
    }

    /**
     * @ORM\Column(name="api_access_control", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="*")
     * @var string|null API "Access-Control-Allow-Origin" header
     */
    protected $api_access_control = '';

    public function getApiAccessControl(): string
    {
        return $this->api_access_control ?? '';
    }

    public function setApiAccessControl(?string $apiAccessControl): void
    {
        $this->api_access_control = $this->truncateString($apiAccessControl);
    }

    /**
     * @ORM\Column(name="enable_websockets", type="boolean")
     *
     * @OA\Property(example="false")
     * @var bool Whether to use Websockets for Now Playing data updates.
     */
    protected $enable_websockets = false;

    public function getEnableWebsockets(): bool
    {
        return $this->enable_websockets;
    }

    public function setEnableWebsockets(bool $enableWebsockets): void
    {
        $this->enable_websockets = $enableWebsockets;
    }

    /**
     * Listener Analytics Collection
     *
     * @ORM\Column(name="analytics", type="string", length=50, nullable=true)
     *
     * @OA\Property()
     * @Assert\Choice({Entity\Analytics::LEVEL_NONE, Entity\Analytics::LEVEL_NO_IP, Entity\Analytics::LEVEL_ALL})
     * @var string|null
     */
    protected $analytics = Entity\Analytics::LEVEL_ALL;

    public function getAnalytics(): string
    {
        return $this->analytics ?? Analytics::LEVEL_ALL;
    }

    public function setAnalytics(?string $analytics): void
    {
        $this->analytics = $this->truncateString($analytics, 50);
    }

    /**
     * @ORM\Column(name="check_for_updates", type="boolean")
     *
     * @OA\Property(example="true")
     * @var bool Check for Updates and Announcements
     */
    protected $check_for_updates = true;

    public function getCheckForUpdates(): bool
    {
        return $this->check_for_updates;
    }

    public function setCheckForUpdates(bool $checkForUpdates): void
    {
        $this->check_for_updates = $checkForUpdates;
    }

    /**
     * @ORM\Column(name="update_results", type="json", nullable=true)
     *
     * @OA\Property(example="")
     * @var mixed[]|null Results of the latest update check.
     *
     * @AuditLog\AuditIgnore
     */
    protected $update_results = null;

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
     * @ORM\Column(name="update_last_run", type="integer")
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when updates were last checked.
     *
     * @AuditLog\AuditIgnore
     */
    protected $update_last_run = 0;

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
     * @ORM\Column(name="public_theme", type="string", length=50, nullable=true)
     *
     * @OA\Property(example="light")
     * @Assert\Choice({Customization::THEME_BROWSER, Customization::THEME_LIGHT, Customization::THEME_DARK})
     * @var string|null Base Theme for Public Pages
     */
    protected $public_theme = Customization::DEFAULT_THEME;

    public function getPublicTheme(): string
    {
        return $this->public_theme ?? Customization::DEFAULT_THEME;
    }

    public function setPublicTheme(?string $publicTheme): void
    {
        $this->public_theme = $publicTheme;
    }

    /**
     * @ORM\Column(name="hide_album_art", type="boolean")
     *
     * @OA\Property(example="false")
     * @var bool Hide Album Art on Public Pages
     */
    protected $hide_album_art = false;

    public function getHideAlbumArt(): bool
    {
        return $this->hide_album_art;
    }

    public function setHideAlbumArt(bool $hideAlbumArt): void
    {
        $this->hide_album_art = $hideAlbumArt;
    }

    /**
     * @ORM\Column(name="homepage_redirect_url", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="https://example.com/")
     * @var string|null Homepage Redirect URL
     */
    protected $homepage_redirect_url = null;

    public function getHomepageRedirectUrl(): ?string
    {
        return $this->homepage_redirect_url;
    }

    public function setHomepageRedirectUrl(?string $homepageRedirectUrl): void
    {
        $this->homepage_redirect_url = $this->truncateString($homepageRedirectUrl);
    }

    /**
     * @ORM\Column(name="default_album_art_url", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="https://example.com/image.jpg")
     * @var string|null Default Album Art URL
     */
    protected $default_album_art_url = null;

    public function getDefaultAlbumArtUrl(): ?string
    {
        return $this->default_album_art_url;
    }

    public function setDefaultAlbumArtUrl(?string $defaultAlbumArtUrl): void
    {
        $this->default_album_art_url = $this->truncateString($defaultAlbumArtUrl);
    }

    /**
     * @ORM\Column(name="use_external_album_art_when_processing_media", type="boolean")
     *
     * @OA\Property(example="false")
     * @var bool Attempt to fetch album art from external sources when processing media.
     */
    protected $use_external_album_art_when_processing_media = false;

    public function getUseExternalAlbumArtWhenProcessingMedia(): bool
    {
        return $this->use_external_album_art_when_processing_media;
    }

    public function setUseExternalAlbumArtWhenProcessingMedia(bool $useExternalAlbumArtWhenProcessingMedia): void
    {
        $this->use_external_album_art_when_processing_media = $useExternalAlbumArtWhenProcessingMedia;
    }

    /**
     * @ORM\Column(name="use_external_album_art_in_apis", type="boolean")
     *
     * @OA\Property(example="false")
     * @var bool Attempt to fetch album art from external sources in API requests.
     */
    protected $use_external_album_art_in_apis = false;

    public function getUseExternalAlbumArtInApis(): bool
    {
        return $this->use_external_album_art_in_apis;
    }

    public function setUseExternalAlbumArtInApis(bool $useExternalAlbumArtInApis): void
    {
        $this->use_external_album_art_in_apis = $useExternalAlbumArtInApis;
    }

    /**
     * @ORM\Column(name="last_fm_api_key", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="SAMPLE-API-KEY")
     * @var string|null An API key to connect to Last.fm services, if provided.
     */
    protected $last_fm_api_key = null;

    public function getLastFmApiKey(): ?string
    {
        return $this->last_fm_api_key;
    }

    public function setLastFmApiKey(?string $lastFmApiKey): void
    {
        $lastFmApiKey = trim($lastFmApiKey);
        $lastFmApiKey = (!empty($lastFmApiKey)) ? $lastFmApiKey : null;

        $this->last_fm_api_key = $this->truncateString($lastFmApiKey);
    }

    /**
     * @ORM\Column(name="hide_product_name", type="boolean")
     *
     * @OA\Property(example="false")
     * @var bool Hide AzuraCast Branding on Public Pages
     */
    protected $hide_product_name = false;

    public function getHideProductName(): bool
    {
        return $this->hide_product_name;
    }

    public function setHideProductName(bool $hideProductName): void
    {
        $this->hide_product_name = $hideProductName;
    }

    /**
     * @ORM\Column(name="public_custom_css", type="text", nullable=true)
     *
     * @OA\Property(example="")
     * @var string|null Custom CSS for Public Pages
     */
    protected $public_custom_css = null;

    public function getPublicCustomCss(): ?string
    {
        return $this->public_custom_css;
    }

    public function setPublicCustomCss(?string $publicCustomCss): void
    {
        $this->public_custom_css = $publicCustomCss;
    }

    /**
     * @ORM\Column(name="public_custom_js", type="text", nullable=true)
     *
     * @OA\Property(example="")
     * @var string|null Custom JS for Public Pages
     */
    protected $public_custom_js = null;

    public function getPublicCustomJs(): ?string
    {
        return $this->public_custom_js;
    }

    public function setPublicCustomJs(?string $publicCustomJs): void
    {
        $this->public_custom_js = $publicCustomJs;
    }

    /**
     * @ORM\Column(name="internal_custom_css", type="text", nullable=true)
     *
     * @OA\Property(example="")
     * @var string|null Custom CSS for Internal Pages
     */
    protected $internal_custom_css = null;

    public function getInternalCustomCss(): ?string
    {
        return $this->internal_custom_css;
    }

    public function setInternalCustomCss(?string $internalCustomCss): void
    {
        $this->internal_custom_css = $internalCustomCss;
    }

    /**
     * @ORM\Column(name="backup_enabled", type="boolean")
     *
     * @OA\Property(example="false")
     * @var bool Whether backup is enabled.
     */
    protected $backup_enabled = false;

    public function isBackupEnabled(): bool
    {
        return $this->backup_enabled;
    }

    public function setBackupEnabled(bool $backupEnabled): void
    {
        $this->backup_enabled = $backupEnabled;
    }

    /**
     * @ORM\Column(name="backup_time_code", type="string", length=4, nullable=true)
     *
     * @OA\Property(example=400)
     * @var string|null The timecode (i.e. 400 for 4:00AM) when automated backups should run.
     */
    protected $backup_time_code = null;

    public function getBackupTimeCode(): ?string
    {
        return $this->backup_time_code;
    }

    public function setBackupTimeCode(?string $backupTimeCode): void
    {
        $this->backup_time_code = $backupTimeCode;
    }

    /**
     * @ORM\Column(name="backup_exclude_media", type="boolean")
     *
     * @OA\Property(example="false")
     * @var bool Whether to exclude media in automated backups.
     */
    protected $backup_exclude_media = false;

    public function getBackupExcludeMedia(): bool
    {
        return $this->backup_exclude_media;
    }

    public function setBackupExcludeMedia(bool $backupExcludeMedia): void
    {
        $this->backup_exclude_media = $backupExcludeMedia;
    }

    /**
     * @ORM\Column(name="backup_keep_copies", type="smallint")
     *
     * @OA\Property(example=2)
     * @var int Number of backups to keep, or infinite if zero/null.
     */
    protected $backup_keep_copies = 0;

    public function getBackupKeepCopies(): int
    {
        return $this->backup_keep_copies;
    }

    public function setBackupKeepCopies(int $backupKeepCopies): void
    {
        $this->backup_keep_copies = $this->truncateSmallInt($backupKeepCopies);
    }

    /**
     * @ORM\Column(name="backup_storage_location", type="integer", nullable=true)
     *
     * @OA\Property(example=1)
     * @var int|null The storage location ID for automated backups.
     */
    protected $backup_storage_location = null;

    public function getBackupStorageLocation(): ?int
    {
        return $this->backup_storage_location;
    }

    public function setBackupStorageLocation(?int $backupStorageLocation): void
    {
        $this->backup_storage_location = $backupStorageLocation;
    }

    /**
     * @ORM\Column(name="backup_last_run", type="integer")
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when automated backup was last run.
     *
     * @AuditLog\AuditIgnore
     */
    protected $backup_last_run = 0;

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
     * @ORM\Column(name="backup_last_result", type="text", nullable=true)
     *
     * @OA\Property(example="")
     * @var string|null The result of the latest automated backup task.
     *
     * @AuditLog\AuditIgnore
     */
    protected $backup_last_result = null;

    public function getBackupLastResult(): ?string
    {
        return $this->backup_last_result;
    }

    public function setBackupLastResult(?string $backupLastResult): void
    {
        $this->backup_last_result = $backupLastResult;
    }

    /**
     * @ORM\Column(name="backup_last_output", type="text", nullable=true)
     *
     * @OA\Property(example="")
     * @var string|null The output of the latest automated backup task.
     *
     * @AuditLog\AuditIgnore
     */
    protected $backup_last_output = null;

    public function getBackupLastOutput(): ?string
    {
        return $this->backup_last_output;
    }

    public function setBackupLastOutput(?string $backupLastOutput): void
    {
        $this->backup_last_output = $backupLastOutput;
    }

    /**
     * @ORM\Column(name="setup_complete_time", type="integer")
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when setup was last completed.
     */
    protected $setup_complete_time = 0;

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
     * @ORM\Column(name="nowplaying", type="json", nullable=true)
     *
     * @OA\Property(example="")
     * @var mixed[]|null The current cached now playing data.
     *
     * @AuditLog\AuditIgnore
     */
    protected $nowplaying = null;

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
     * @ORM\Column(name="sync_nowplaying_last_run", type="integer")
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when the now playing sync task was last run.
     *
     * @AuditLog\AuditIgnore
     */
    protected $sync_nowplaying_last_run = 0;

    public function getSyncNowplayingLastRun(): int
    {
        return $this->sync_nowplaying_last_run;
    }

    public function setSyncNowplayingLastRun(int $syncNowplayingLastRun): void
    {
        $this->sync_nowplaying_last_run = $syncNowplayingLastRun;
    }

    /**
     * @ORM\Column(name="sync_short_last_run", type="integer")
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when the 60-second "short" sync task was last run.
     *
     * @AuditLog\AuditIgnore
     */
    protected $sync_short_last_run = 0;

    public function getSyncShortLastRun(): int
    {
        return $this->sync_short_last_run;
    }

    public function setSyncShortLastRun(int $syncShortLastRun): void
    {
        $this->sync_short_last_run = $syncShortLastRun;
    }

    /**
     * @ORM\Column(name="sync_medium_last_run", type="integer")
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when the 5-minute "medium" sync task was last run.
     *
     * @AuditLog\AuditIgnore
     */
    protected $sync_medium_last_run = 0;

    public function getSyncMediumLastRun(): int
    {
        return $this->sync_medium_last_run;
    }

    public function setSyncMediumLastRun(int $syncMediumLastRun): void
    {
        $this->sync_medium_last_run = $syncMediumLastRun;
    }

    /**
     * @ORM\Column(name="sync_long_last_run", type="integer")
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when the 1-hour "long" sync task was last run.
     *
     * @AuditLog\AuditIgnore
     */
    protected $sync_long_last_run = 0;

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
     * @ORM\Column(name="external_ip", type="string", length=45, nullable=true)
     *
     * @OA\Property(example="192.168.1.1")
     * @var string|null This installation's external IP.
     *
     * @AuditLog\AuditIgnore
     */
    protected $external_ip = null;

    public function getExternalIp(): ?string
    {
        return $this->external_ip;
    }

    public function setExternalIp(?string $externalIp): void
    {
        $this->external_ip = $externalIp;
    }

    /**
     * @ORM\Column(name="geolite_license_key", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="")
     * @var string|null The license key for the Maxmind Geolite download.
     */
    protected $geolite_license_key = null;

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
     * @ORM\Column(name="geolite_last_run", type="integer")
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when the Maxmind Geolite was last downloaded.
     *
     * @AuditLog\AuditIgnore
     */
    protected $geolite_last_run = 0;

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
     * @ORM\Column(name="enable_advanced_features", type="boolean")
     *
     * @OA\Property(example=false)
     * @var bool Whether to enable "advanced" functionality in the system that is intended for power users.
     */
    protected $enable_advanced_features = false;

    public function getEnableAdvancedFeatures(): bool
    {
        return $this->enable_advanced_features;
    }

    public function setEnableAdvancedFeatures(bool $enableAdvancedFeatures): void
    {
        $this->enable_advanced_features = $enableAdvancedFeatures;
    }

    /**
     * @ORM\Column(name="mail_enabled", type="boolean")
     *
     * @OA\Property(example="true")
     * @var bool Enable e-mail delivery across the application.
     */
    protected $mail_enabled = false;

    public function getMailEnabled(): bool
    {
        return $this->mail_enabled;
    }

    public function setMailEnabled(bool $mailEnabled): void
    {
        $this->mail_enabled = $mailEnabled;
    }

    /**
     * @ORM\Column(name="mail_sender_name", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="AzuraCast")
     * @var string|null The name of the sender of system e-mails.
     */
    protected $mail_sender_name = '';

    public function getMailSenderName(): string
    {
        return $this->mail_sender_name ?? '';
    }

    public function setMailSenderName(?string $mailSenderName): void
    {
        $this->mail_sender_name = $mailSenderName;
    }

    /**
     * @ORM\Column(name="mail_sender_email", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="example@example.com")
     * @var string|null The e-mail address of the sender of system e-mails.
     */
    protected $mail_sender_email = '';

    public function getMailSenderEmail(): string
    {
        return $this->mail_sender_email ?? '';
    }

    public function setMailSenderEmail(?string $mailSenderEmail): void
    {
        $this->mail_sender_email = $mailSenderEmail;
    }

    /**
     * @ORM\Column(name="mail_smtp_host", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="smtp.example.com")
     * @var string|null The host to send outbound SMTP mail.
     */
    protected $mail_smtp_host = '';

    public function getMailSmtpHost(): string
    {
        return $this->mail_smtp_host ?? '';
    }

    public function setMailSmtpHost(?string $mailSmtpHost): void
    {
        $this->mail_smtp_host = $mailSmtpHost;
    }

    /**
     * @ORM\Column(name="mail_smtp_port", type="smallint")
     *
     * @OA\Property(example=465)
     * @var int The port for sending outbound SMTP mail.
     */
    protected $mail_smtp_port = 0;

    public function getMailSmtpPort(): int
    {
        return $this->mail_smtp_port;
    }

    public function setMailSmtpPort(int $mailSmtpPort): void
    {
        $this->mail_smtp_port = $this->truncateSmallInt($mailSmtpPort);
    }

    /**
     * @ORM\Column(name="mail_smtp_username", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="username")
     * @var string|null The username when connecting to SMTP mail.
     */
    protected $mail_smtp_username = '';

    public function getMailSmtpUsername(): string
    {
        return $this->mail_smtp_username ?? '';
    }

    public function setMailSmtpUsername(?string $mailSmtpUsername): void
    {
        $this->mail_smtp_username = $this->truncateString($mailSmtpUsername);
    }

    /**
     * @ORM\Column(name="mail_smtp_password", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="password")
     * @var string|null The password when connecting to SMTP mail.
     */
    protected $mail_smtp_password = '';

    public function getMailSmtpPassword(): string
    {
        return $this->mail_smtp_password ?? '';
    }

    public function setMailSmtpPassword(?string $mailSmtpPassword): void
    {
        $this->mail_smtp_password = $mailSmtpPassword;
    }

    /**
     * @ORM\Column(name="mail_smtp_secure", type="boolean")
     *
     * @OA\Property(example="true")
     * @var bool Whether to use a secure (TLS) connection when sending SMTP mail.
     */
    protected $mail_smtp_secure = true;

    public function getMailSmtpSecure(): bool
    {
        return $this->mail_smtp_secure;
    }

    public function setMailSmtpSecure(bool $mailSmtpSecure): void
    {
        $this->mail_smtp_secure = $mailSmtpSecure;
    }

    /**
     * @ORM\Column(name="avatar_service", type="string", length=25, nullable=true)
     *
     * @OA\Property(example="libravatar")
     * @var string|null The external avatar service to use when fetching avatars.
     */
    protected $avatar_service = null;

    public function getAvatarService(): string
    {
        return $this->avatar_service ?? Avatar::DEFAULT_SERVICE;
    }

    public function setAvatarService(?string $avatarService): void
    {
        $this->avatar_service = $this->truncateString($avatarService, 25);
    }

    /**
     * @ORM\Column(name="avatar_default_url", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="")
     * @var string|null The default avatar URL.
     */
    protected $avatar_default_url = null;

    public function getAvatarDefaultUrl(): string
    {
        return $this->avatar_default_url ?? Avatar::DEFAULT_AVATAR;
    }

    public function setAvatarDefaultUrl(?string $avatarDefaultUrl): void
    {
        $this->avatar_default_url = $avatarDefaultUrl;
    }

    /**
     * AuditIdentifier filler function
     * @AuditLog\AuditIdentifier
     */
    public function getAuditIdentifier(): string
    {
        return 'Settings';
    }
}
