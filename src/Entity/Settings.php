<?php

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Customization;
use App\Entity;
use App\Event\GetSyncTasks;
use App\Service\Avatar;
use App\Traits\AvailableStaticallyTrait;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema(type="object", schema="Settings")
 */
class Settings
{
    use AvailableStaticallyTrait;

    /**
     * @OA\Property(example="https://your.azuracast.site")
     * @var string Site Base URL
     */
    protected string $baseUrl = '';

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @OA\Property(example="My AzuraCast Instance")
     * @var string|null AzuraCast Instance Name
     */
    protected ?string $instanceName = null;

    public function getInstanceName(): ?string
    {
        return $this->instanceName;
    }

    public function setInstanceName(?string $instanceName): void
    {
        $this->instanceName = $instanceName;
    }

    /**
     * @OA\Property(example="false")
     * @var bool Prefer Browser URL (If Available)
     */
    protected bool $preferBrowserUrl = false;

    public function getPreferBrowserUrl(): bool
    {
        return (bool)($this->preferBrowserUrl);
    }

    public function setPreferBrowserUrl(bool $preferBrowserUrl): void
    {
        $this->preferBrowserUrl = $preferBrowserUrl;
    }

    /**
     * @OA\Property(example="false")
     * @var bool Use Web Proxy for Radio
     */
    protected bool $useRadioProxy = false;

    public function getUseRadioProxy(): bool
    {
        return (bool)($this->useRadioProxy);
    }

    public function setUseRadioProxy(bool $useRadioProxy): void
    {
        $this->useRadioProxy = $useRadioProxy;
    }

    /**
     * @OA\Property()
     * @Assert\Choice({0,14,30,60,365,730})
     * @var int Days of Playback History to Keep
     */
    protected int $historyKeepDays = Entity\SongHistory::DEFAULT_DAYS_TO_KEEP;

    public function getHistoryKeepDays(): int
    {
        return $this->historyKeepDays;
    }

    public function setHistoryKeepDays(int $historyKeepDays): void
    {
        $this->historyKeepDays = $historyKeepDays;
    }

    /**
     * @OA\Property(example="false")
     * @var bool Always Use HTTPS
     */
    protected bool $alwaysUseSsl = false;

    public function getAlwaysUseSsl(): bool
    {
        return (bool)$this->alwaysUseSsl;
    }

    public function setAlwaysUseSsl(bool $alwaysUseSsl): void
    {
        $this->alwaysUseSsl = $alwaysUseSsl;
    }

    /**
     * @OA\Property(example="*")
     * @var string API "Access-Control-Allow-Origin" header
     */
    protected string $apiAccessControl = '';

    public function getApiAccessControl(): string
    {
        return $this->apiAccessControl;
    }

    public function setApiAccessControl(string $apiAccessControl): void
    {
        $this->apiAccessControl = $apiAccessControl;
    }

    /**
     * @OA\Property(example="false")
     * @var bool Whether to use Websockets for Now Playing data updates.
     */
    protected bool $enableWebsockets = false;

    public function getEnableWebsockets(): bool
    {
        return (bool)$this->enableWebsockets;
    }

    public function setEnableWebsockets(bool $enableWebsockets): void
    {
        $this->enableWebsockets = $enableWebsockets;
    }

    /**
     * Listener Analytics Collection
     *
     * @OA\Property()
     * @Assert\Choice({Entity\Analytics::LEVEL_NONE, Entity\Analytics::LEVEL_NO_IP, Entity\Analytics::LEVEL_ALL})
     * @var string
     */
    protected string $analytics = Entity\Analytics::LEVEL_ALL;

    public function getAnalytics(): string
    {
        return $this->analytics;
    }

    public function setAnalytics(string $analytics): void
    {
        $this->analytics = $analytics;
    }

    /**
     * @OA\Property(example="true")
     * @var bool Check for Updates and Announcements
     */
    protected bool $checkForUpdates = true;

    public function getCheckForUpdates(): bool
    {
        return $this->checkForUpdates;
    }

    public function setCheckForUpdates(bool $checkForUpdates): void
    {
        $this->checkForUpdates = $checkForUpdates;
    }

    /**
     * @OA\Property(example="")
     * @var string|null The unique identifier for this installation (for update checks).
     */
    protected ?string $appUniqueIdentifier = null;

    public function getAppUniqueIdentifier(): ?string
    {
        return $this->appUniqueIdentifier;
    }

    public function setAppUniqueIdentifier(?string $appUniqueIdentifier): void
    {
        $this->appUniqueIdentifier = $appUniqueIdentifier;
    }

    /**
     * @OA\Property(example="")
     * @var mixed[]|null Results of the latest update check.
     *
     * @AuditLog\AuditIgnore
     */
    protected ?array $updateResults = null;

    /**
     * @return mixed[]|null
     */
    public function getUpdateResults(): ?array
    {
        return $this->updateResults;
    }

    public function setUpdateResults(?array $updateResults): void
    {
        $this->updateResults = $updateResults;
    }

    /**
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when updates were last checked.
     *
     * @AuditLog\AuditIgnore
     */
    protected int $updateLastRun = 0;

    public function getUpdateLastRun(): int
    {
        return $this->updateLastRun;
    }

    public function setUpdateLastRun(int $updateLastRun): void
    {
        $this->updateLastRun = $updateLastRun;
    }

    public function updateUpdateLastRun(): void
    {
        $this->setUpdateLastRun(time());
    }

    /**
     * @OA\Property(example="light")
     * @Assert\Choice({Customization::THEME_LIGHT, Customization::THEME_DARK})
     * @var string Base Theme for Public Pages
     */
    protected string $publicTheme = Customization::DEFAULT_THEME;

    public function getPublicTheme(): string
    {
        return $this->publicTheme;
    }

    public function setPublicTheme(string $publicTheme): void
    {
        $this->publicTheme = $publicTheme;
    }

    /**
     * @OA\Property(example="false")
     * @var bool Hide Album Art on Public Pages
     */
    protected bool $hideAlbumArt = false;

    public function getHideAlbumArt(): bool
    {
        return $this->hideAlbumArt;
    }

    public function setHideAlbumArt(bool $hideAlbumArt): void
    {
        $this->hideAlbumArt = $hideAlbumArt;
    }

    /**
     * @OA\Property(example="https://example.com/")
     * @var string|null Homepage Redirect URL
     */
    protected ?string $homepageRedirectUrl = null;

    public function getHomepageRedirectUrl(): ?string
    {
        return $this->homepageRedirectUrl;
    }

    public function setHomepageRedirectUrl(?string $homepageRedirectUrl): void
    {
        $this->homepageRedirectUrl = $homepageRedirectUrl;
    }

    /**
     * @OA\Property(example="https://example.com/image.jpg")
     * @var string|null Default Album Art URL
     */
    protected ?string $defaultAlbumArtUrl = null;

    public function getDefaultAlbumArtUrl(): ?string
    {
        return $this->defaultAlbumArtUrl;
    }

    public function setDefaultAlbumArtUrl(?string $defaultAlbumArtUrl): void
    {
        $this->defaultAlbumArtUrl = $defaultAlbumArtUrl;
    }

    /**
     * @OA\Property(example="false")
     * @var bool Attempt to fetch album art from external sources when processing media.
     */
    protected bool $useExternalAlbumArtWhenProcessingMedia = false;

    public function getUseExternalAlbumArtWhenProcessingMedia(): bool
    {
        return $this->useExternalAlbumArtWhenProcessingMedia;
    }

    public function setUseExternalAlbumArtWhenProcessingMedia(bool $useExternalAlbumArtWhenProcessingMedia): void
    {
        $this->useExternalAlbumArtWhenProcessingMedia = $useExternalAlbumArtWhenProcessingMedia;
    }

    /**
     * @OA\Property(example="false")
     * @var bool Attempt to fetch album art from external sources in API requests.
     */
    protected bool $useExternalAlbumArtInApis = false;

    public function getUseExternalAlbumArtInApis(): bool
    {
        return $this->useExternalAlbumArtInApis;
    }

    public function setUseExternalAlbumArtInApis(bool $useExternalAlbumArtInApis): void
    {
        $this->useExternalAlbumArtInApis = $useExternalAlbumArtInApis;
    }

    /**
     * @OA\Property(example="SAMPLE-API-KEY")
     * @var string|null An API key to connect to Last.fm services, if provided.
     */
    protected ?string $lastFmApiKey = null;

    public function getLastFmApiKey(): ?string
    {
        return $this->lastFmApiKey;
    }

    public function setLastFmApiKey(?string $lastFmApiKey): void
    {
        $lastFmApiKey = trim($lastFmApiKey);
        $this->lastFmApiKey = (!empty($lastFmApiKey)) ? $lastFmApiKey : null;
    }

    /**
     * @OA\Property(example="false")
     * @var bool Hide AzuraCast Branding on Public Pages
     */
    protected bool $hideProductName = false;

    public function getHideProductName(): bool
    {
        return (bool)$this->hideProductName;
    }

    public function setHideProductName(bool $hideProductName): void
    {
        $this->hideProductName = $hideProductName;
    }

    /**
     * @OA\Property(example="")
     * @var string|null Custom CSS for Public Pages
     */
    protected ?string $publicCustomCss = null;

    public function getPublicCustomCss(): ?string
    {
        return $this->publicCustomCss;
    }

    public function setPublicCustomCss(?string $publicCustomCss): void
    {
        $this->publicCustomCss = $publicCustomCss;
    }

    /**
     * @OA\Property(example="")
     * @var string|null Custom JS for Public Pages
     */
    protected ?string $publicCustomJs = null;

    public function getPublicCustomJs(): ?string
    {
        return $this->publicCustomJs;
    }

    public function setPublicCustomJs(?string $publicCustomJs): void
    {
        $this->publicCustomJs = $publicCustomJs;
    }

    /**
     * @OA\Property(example="")
     * @var string|null Custom CSS for Internal Pages
     */
    protected ?string $internalCustomCss = null;

    public function getInternalCustomCss(): ?string
    {
        return $this->internalCustomCss;
    }

    public function setInternalCustomCss(?string $internalCustomCss): void
    {
        $this->internalCustomCss = $internalCustomCss;
    }

    /**
     * @OA\Property(example="false")
     * @var bool Whether backup is enabled.
     */
    protected bool $backupEnabled = false;

    public function isBackupEnabled(): bool
    {
        return $this->backupEnabled;
    }

    public function setBackupEnabled(bool $backupEnabled): void
    {
        $this->backupEnabled = $backupEnabled;
    }

    /**
     * @OA\Property(example=400)
     * @var string|null The timecode (i.e. 400 for 4:00AM) when automated backups should run.
     */
    protected ?string $backupTimeCode = null;

    public function getBackupTimeCode(): ?string
    {
        return $this->backupTimeCode;
    }

    public function setBackupTimeCode(?string $backupTimeCode): void
    {
        $this->backupTimeCode = $backupTimeCode;
    }

    /**
     * @OA\Property(example="false")
     * @var bool Whether to exclude media in automated backups.
     */
    protected bool $backupExcludeMedia = false;

    public function getBackupExcludeMedia(): bool
    {
        return $this->backupExcludeMedia;
    }

    public function setBackupExcludeMedia(bool $backupExcludeMedia): void
    {
        $this->backupExcludeMedia = $backupExcludeMedia;
    }

    /**
     * @OA\Property(example=2)
     * @var int Number of backups to keep, or infinite if zero/null.
     */
    protected int $backupKeepCopies = 0;

    public function getBackupKeepCopies(): int
    {
        return $this->backupKeepCopies;
    }

    public function setBackupKeepCopies(int $backupKeepCopies): void
    {
        $this->backupKeepCopies = $backupKeepCopies;
    }

    /**
     * @OA\Property(example=1)
     * @var int|null The storage location ID for automated backups.
     */
    protected ?int $backupStorageLocation = null;

    public function getBackupStorageLocation(): ?int
    {
        return $this->backupStorageLocation;
    }

    public function setBackupStorageLocation(?int $backupStorageLocation): void
    {
        $this->backupStorageLocation = $backupStorageLocation;
    }

    /**
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when automated backup was last run.
     *
     * @AuditLog\AuditIgnore
     */
    protected int $backupLastRun = 0;

    public function getBackupLastRun(): int
    {
        return $this->backupLastRun;
    }

    public function setBackupLastRun(int $backupLastRun): void
    {
        $this->backupLastRun = $backupLastRun;
    }

    public function updateBackupLastRun(): void
    {
        $this->setBackupLastRun(time());
    }

    /**
     * @OA\Property(example="")
     * @var string|null The result of the latest automated backup task.
     *
     * @AuditLog\AuditIgnore
     */
    protected ?string $backupLastResult = null;

    public function getBackupLastResult(): ?string
    {
        return $this->backupLastResult;
    }

    public function setBackupLastResult(?string $backupLastResult): void
    {
        $this->backupLastResult = $backupLastResult;
    }

    /**
     * @OA\Property(example="")
     * @var string|null The output of the latest automated backup task.
     *
     * @AuditLog\AuditIgnore
     */
    protected ?string $backupLastOutput = null;

    public function getBackupLastOutput(): ?string
    {
        return $this->backupLastOutput;
    }

    public function setBackupLastOutput(?string $backupLastOutput): void
    {
        $this->backupLastOutput = $backupLastOutput;
    }

    /**
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when setup was last completed.
     */
    protected int $setupCompleteTime = 0;

    public function getSetupCompleteTime(): int
    {
        return $this->setupCompleteTime;
    }

    public function isSetupComplete(): bool
    {
        return (0 !== $this->setupCompleteTime);
    }

    public function setSetupCompleteTime(int $setupCompleteTime): void
    {
        $this->setupCompleteTime = $setupCompleteTime;
    }

    public function updateSetupComplete(): void
    {
        $this->setSetupCompleteTime(time());
    }

    /**
     * @OA\Property(example="")
     * @var mixed[]|null The current cached now playing data.
     *
     * @AuditLog\AuditIgnore
     */
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
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when the now playing sync task was last run.
     *
     * @AuditLog\AuditIgnore
     */
    protected int $syncNowplayingLastRun = 0;

    public function getSyncNowplayingLastRun(): int
    {
        return $this->syncNowplayingLastRun;
    }

    public function setSyncNowplayingLastRun(int $syncNowplayingLastRun): void
    {
        $this->syncNowplayingLastRun = $syncNowplayingLastRun;
    }

    /**
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when the 60-second "short" sync task was last run.
     *
     * @AuditLog\AuditIgnore
     */
    protected int $syncShortLastRun = 0;

    public function getSyncShortLastRun(): int
    {
        return $this->syncShortLastRun;
    }

    public function setSyncShortLastRun(int $syncShortLastRun): void
    {
        $this->syncShortLastRun = $syncShortLastRun;
    }

    /**
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when the 5-minute "medium" sync task was last run.
     *
     * @AuditLog\AuditIgnore
     */
    protected int $syncMediumLastRun = 0;

    public function getSyncMediumLastRun(): int
    {
        return $this->syncMediumLastRun;
    }

    public function setSyncMediumLastRun(int $syncMediumLastRun): void
    {
        $this->syncMediumLastRun = $syncMediumLastRun;
    }

    /**
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when the 1-hour "long" sync task was last run.
     *
     * @AuditLog\AuditIgnore
     */
    protected int $syncLongLastRun = 0;

    public function getSyncLongLastRun(): int
    {
        return $this->syncLongLastRun;
    }

    public function setSyncLongLastRun(int $syncLongLastRun): void
    {
        $this->syncLongLastRun = $syncLongLastRun;
    }

    public function getSyncLastRunTime(string $type): int
    {
        $timesByType = [
            GetSyncTasks::SYNC_NOWPLAYING => $this->syncNowplayingLastRun,
            GetSyncTasks::SYNC_SHORT => $this->syncShortLastRun,
            GetSyncTasks::SYNC_MEDIUM => $this->syncMediumLastRun,
            GetSyncTasks::SYNC_LONG => $this->syncLongLastRun,
        ];

        return $timesByType[$type] ?? 0;
    }

    public function updateSyncLastRunTime(string $type): void
    {
        switch ($type) {
            case GetSyncTasks::SYNC_NOWPLAYING:
                $this->syncNowplayingLastRun = time();
                break;

            case GetSyncTasks::SYNC_SHORT:
                $this->syncShortLastRun = time();
                break;

            case GetSyncTasks::SYNC_MEDIUM:
                $this->syncMediumLastRun = time();
                break;

            case GetSyncTasks::SYNC_LONG:
                $this->syncLongLastRun = time();
                break;
        }
    }

    /**
     * @OA\Property(example="192.168.1.1")
     * @var string|null This installation's external IP.
     *
     * @AuditLog\AuditIgnore
     */
    protected ?string $externalIp = null;

    public function getExternalIp(): ?string
    {
        return $this->externalIp;
    }

    public function setExternalIp(?string $externalIp): void
    {
        $this->externalIp = $externalIp;
    }

    /**
     * @OA\Property(example="")
     * @var string|null The license key for the Maxmind Geolite download.
     */
    protected ?string $geoliteLicenseKey = null;

    public function getGeoliteLicenseKey(): ?string
    {
        return (null === $this->geoliteLicenseKey)
            ? null
            : trim($this->geoliteLicenseKey);
    }

    public function setGeoliteLicenseKey(?string $geoliteLicenseKey): void
    {
        $this->geoliteLicenseKey = $geoliteLicenseKey;
    }

    /**
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The UNIX timestamp when the Maxmind Geolite was last downloaded.
     *
     * @AuditLog\AuditIgnore
     */
    protected int $geoliteLastRun = 0;

    public function getGeoliteLastRun(): int
    {
        return $this->geoliteLastRun;
    }

    public function setGeoliteLastRun(int $geoliteLastRun): void
    {
        $this->geoliteLastRun = $geoliteLastRun;
    }

    public function updateGeoliteLastRun(): void
    {
        $this->setGeoliteLastRun(time());
    }

    /**
     * @OA\Property(example=false)
     * @var bool Whether to enable "advanced" functionality in the system that is intended for power users.
     */
    protected bool $enableAdvancedFeatures = false;

    public function getEnableAdvancedFeatures(): bool
    {
        return $this->enableAdvancedFeatures;
    }

    public function setEnableAdvancedFeatures(bool $enableAdvancedFeatures): void
    {
        $this->enableAdvancedFeatures = $enableAdvancedFeatures;
    }

    /**
     * @OA\Property(example="true")
     * @var bool Enable e-mail delivery across the application.
     */
    protected bool $mailEnabled = false;

    public function getMailEnabled(): bool
    {
        return $this->mailEnabled;
    }

    public function setMailEnabled(bool $mailEnabled): void
    {
        $this->mailEnabled = $mailEnabled;
    }

    /**
     * @OA\Property(example="AzuraCast")
     * @var string The name of the sender of system e-mails.
     */
    protected string $mailSenderName = '';

    public function getMailSenderName(): string
    {
        return $this->mailSenderName;
    }

    public function setMailSenderName(string $mailSenderName): void
    {
        $this->mailSenderName = $mailSenderName;
    }

    /**
     * @OA\Property(example="example@example.com")
     * @var string The e-mail address of the sender of system e-mails.
     */
    protected string $mailSenderEmail = '';

    public function getMailSenderEmail(): string
    {
        return $this->mailSenderEmail;
    }

    public function setMailSenderEmail(string $mailSenderEmail): void
    {
        $this->mailSenderEmail = $mailSenderEmail;
    }

    /**
     * @OA\Property(example="smtp.example.com")
     * @var string The host to send outbound SMTP mail.
     */
    protected string $mailSmtpHost = '';

    public function getMailSmtpHost(): string
    {
        return $this->mailSmtpHost;
    }

    public function setMailSmtpHost(string $mailSmtpHost): void
    {
        $this->mailSmtpHost = $mailSmtpHost;
    }

    /**
     * @OA\Property(example=465)
     * @var int The port for sending outbound SMTP mail.
     */
    protected int $mailSmtpPort = 0;

    public function getMailSmtpPort(): int
    {
        return $this->mailSmtpPort;
    }

    public function setMailSmtpPort(int $mailSmtpPort): void
    {
        $this->mailSmtpPort = $mailSmtpPort;
    }

    /**
     * @OA\Property(example="username")
     * @var string The username when connecting to SMTP mail.
     */
    protected string $mailSmtpUsername = '';

    public function getMailSmtpUsername(): string
    {
        return $this->mailSmtpUsername;
    }

    public function setMailSmtpUsername(string $mailSmtpUsername): void
    {
        $this->mailSmtpUsername = $mailSmtpUsername;
    }

    /**
     * @OA\Property(example="password")
     * @var string The password when connecting to SMTP mail.
     */
    protected string $mailSmtpPassword = '';

    public function getMailSmtpPassword(): string
    {
        return $this->mailSmtpPassword;
    }

    public function setMailSmtpPassword(string $mailSmtpPassword): void
    {
        $this->mailSmtpPassword = $mailSmtpPassword;
    }

    /**
     * @OA\Property(example="true")
     * @var bool Whether to use a secure (TLS) connection when sending SMTP mail.
     */
    protected bool $mailSmtpSecure = true;

    public function getMailSmtpSecure(): bool
    {
        return $this->mailSmtpSecure;
    }

    public function setMailSmtpSecure(bool $mailSmtpSecure): void
    {
        $this->mailSmtpSecure = $mailSmtpSecure;
    }

    /**
     * @OA\Property(example="libravatar")
     * @var string The external avatar service to use when fetching avatars.
     */
    protected ?string $avatarService = null;

    public function getAvatarService(): string
    {
        return $this->avatarService ?? Avatar::DEFAULT_SERVICE;
    }

    public function setAvatarService(?string $avatarService): void
    {
        $this->avatarService = $avatarService;
    }

    /**
     * @OA\Property(example="")
     * @var string The default avatar URL.
     */
    protected ?string $avatarDefaultUrl = null;

    public function getAvatarDefaultUrl(): string
    {
        return $this->avatarDefaultUrl ?? Avatar::DEFAULT_AVATAR;
    }

    public function setAvatarDefaultUrl(?string $avatarDefaultUrl): void
    {
        $this->avatarDefaultUrl = $avatarDefaultUrl;
    }
}
