<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\Generator\UuidV6Generator;
use App\Entity\Api\Admin\UpdateDetails;
use App\Entity\Enums\AnalyticsLevel;
use App\Entity\Enums\IpSources;
use App\Enums\SupportedThemes;
use App\OpenApi;
use App\Utilities\Types;
use App\Utilities\Urls;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Stringable;
use Symfony\Component\Serializer\Attribute as Serializer;

#[
    OA\Schema(schema: "Settings", type: "object"),
    ORM\Entity,
    ORM\Table(name: 'settings'),
    Attributes\Auditable
]
final class Settings implements Stringable
{
    use Traits\TruncateStrings;
    use Traits\TruncateInts;

    // Sorting groups for settings, as used in Symfony serialization.
    public const string GROUP_GENERAL = 'general';
    public const string GROUP_BRANDING = 'branding';
    public const string GROUP_BACKUP = 'backup';
    public const string GROUP_GEO_IP = 'geo_ip';

    public const array VALID_GROUPS = [
        self::GROUP_GENERAL,
        self::GROUP_BRANDING,
        self::GROUP_BACKUP,
        self::GROUP_GEO_IP,
    ];

    #[
        OA\Property,
        ORM\Column(type: 'guid', unique: true),
        ORM\Id,
        ORM\GeneratedValue(strategy: 'CUSTOM'),
        ORM\CustomIdGenerator(UuidV6Generator::class)
    ]
    public string $app_unique_identifier {
        get {
            if (!isset($this->app_unique_identifier)) {
                throw new RuntimeException('Application Unique ID not generated yet.');
            }

            return $this->app_unique_identifier;
        }
    }

    #[
        OA\Property(description: "Site Base URL", example: "https://your.azuracast.site"),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?string $base_url = '' {
        set {
            $value = Types::stringOrNull($value, true);

            if ($value !== null) {
                // Filter the base URL to avoid trailing slashes and other problems.
                $baseUri = Urls::parseUserUrl(
                    $value,
                    'System Base URL'
                );

                $value = (string)$baseUri;
            }

            $this->base_url = $this->truncateNullableString($value);
        }
    }

    public function getBaseUrlAsUri(): ?UriInterface
    {
        return Urls::tryParseUserUrl(
            $this->base_url,
            'System Base URL',
        );
    }

    #[
        OA\Property(description: "AzuraCast Instance Name", example: "My AzuraCast Instance"),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?string $instance_name = null {
        set => $this->truncateNullableString($value);
    }

    #[
        OA\Property(description: "Prefer Browser URL (If Available)", example: "false"),
        ORM\Column,
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public bool $prefer_browser_url = true;

    #[
        OA\Property(description: "Use Web Proxy for Radio", example: "false"),
        ORM\Column,
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public bool $use_radio_proxy = true;

    #[
        OA\Property(description: "Days of Playback History to Keep"),
        ORM\Column(type: 'smallint'),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public int $history_keep_days = SongHistory::DEFAULT_DAYS_TO_KEEP {
        set => $this->truncateSmallInt($value);
    }

    #[
        OA\Property(description: "Always Use HTTPS", example: "false"),
        ORM\Column,
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public bool $always_use_ssl = false;

    #[
        OA\Property(description: "API 'Access-Control-Allow-Origin' header", example: "*"),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?string $api_access_control = '' {
        get => Types::stringOrNull($this->api_access_control, true);
        set => $this->truncateNullableString($value);
    }

    #[
        OA\Property(
            description: "Whether to use high-performance static JSON for Now Playing data updates.",
            example: "false"
        ),
        ORM\Column,
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public bool $enable_static_nowplaying = false;

    #[
        OA\Property(description: "Listener Analytics Collection"),
        ORM\Column(type: 'string', length: 50, nullable: true, enumType: AnalyticsLevel::class),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?AnalyticsLevel $analytics = null;
    
    public function isAnalyticsEnabled(): bool
    {
        return AnalyticsLevel::None !== ($this->analytics ?? AnalyticsLevel::default());
    }

    #[
        OA\Property(description: "Check for Updates and Announcements", example: "true"),
        ORM\Column,
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public bool $check_for_updates = true;

    #[
        ORM\Column(name: 'update_results', type: 'json', nullable: true),
        Attributes\AuditIgnore
    ]
    private ?array $update_results_raw = null;

    #[OA\Property(description: "Results of the latest update check.", example: "")]
    public ?UpdateDetails $update_results {
        get => $this->update_results_raw !== null
            ? UpdateDetails::fromArray($this->update_results_raw)
            : null;
        set(UpdateDetails|array|null $value) {
            if ($value instanceof UpdateDetails) {
                $value = get_object_vars($value);
            }
            $this->update_results_raw = $value;
            $this->update_last_run = time();
        }
    }

    #[
        OA\Property(
            description: "The UNIX timestamp when updates were last checked.",
            example: OpenApi::SAMPLE_TIMESTAMP
        ),
        ORM\Column,
        Attributes\AuditIgnore
    ]
    public int $update_last_run = 0;

    #[
        OA\Property(description: "Base Theme for Public Pages", example: "light"),
        ORM\Column(type: 'string', length: 50, nullable: true, enumType: SupportedThemes::class),
        Serializer\Groups(self::GROUP_BRANDING)
    ]
    public ?SupportedThemes $public_theme = null;

    #[
        OA\Property(description: "Hide Album Art on Public Pages", example: "false"),
        ORM\Column,
        Serializer\Groups(self::GROUP_BRANDING)
    ]
    public bool $hide_album_art = false;

    #[
        OA\Property(description: "Homepage Redirect URL", example: "https://example.com/"),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_BRANDING)
    ]
    public ?string $homepage_redirect_url = null {
        get => Types::stringOrNull($this->homepage_redirect_url, true);
        set => $this->truncateNullableString($value);
    }

    #[
        OA\Property(description: "Default Album Art URL", example: "https://example.com/image.jpg"),
        ORM\Column(nullable: true),
        Serializer\Groups(self::GROUP_BRANDING)
    ]
    public ?string $default_album_art_url = null {
        set => $this->truncateNullableString($value, 255, true);
    }

    public function getDefaultAlbumArtUrlAsUri(): ?UriInterface
    {
        return Urls::tryParseUserUrl(
            $this->default_album_art_url,
            'Default Album Art URL',
            false
        );
    }

    #[
        OA\Property(
            description: "Attempt to fetch album art from external sources when processing media.",
            example: "false"
        ),
        ORM\Column,
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public bool $use_external_album_art_when_processing_media = false;

    #[
        OA\Property(
            description: "Attempt to fetch album art from external sources in API requests.",
            example: "false"
        ),
        ORM\Column,
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public bool $use_external_album_art_in_apis = false;

    #[
        OA\Property(
            description: "An API key to connect to Last.fm services, if provided.",
            example: "SAMPLE-API-KEY"
        ),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?string $last_fm_api_key = null {
        set => $this->truncateNullableString($value, 255, true);
    }

    #[
        OA\Property(description: "Hide AzuraCast Branding on Public Pages", example: "false"),
        ORM\Column,
        Serializer\Groups(self::GROUP_BRANDING)
    ]
    public bool $hide_product_name = false;

    #[
        OA\Property(description: "Custom CSS for Public Pages", example: ""),
        ORM\Column(type: 'text', nullable: true),
        Serializer\Groups(self::GROUP_BRANDING)
    ]
    public ?string $public_custom_css = null {
        set => Types::stringOrNull($value, true);
    }

    #[
        OA\Property(description: "Custom JS for Public Pages", example: ""),
        ORM\Column(type: 'text', nullable: true),
        Serializer\Groups(self::GROUP_BRANDING)
    ]
    public ?string $public_custom_js = null {
        set => Types::stringOrNull($value, true);
    }

    #[
        OA\Property(description: "Custom CSS for Internal Pages", example: ""),
        ORM\Column(type: 'text', nullable: true),
        Serializer\Groups(self::GROUP_BRANDING)
    ]
    public ?string $internal_custom_css = null {
        set => Types::stringOrNull($value, true);
    }

    #[
        OA\Property(description: "Whether backup is enabled.", example: "false"),
        ORM\Column,
        Serializer\Groups(self::GROUP_BACKUP)
    ]
    public bool $backup_enabled = false;

    #[
        OA\Property(
            description: "The timecode (i.e. 400 for 4:00AM) when automated backups should run.",
            example: 400
        ),
        ORM\Column(length: 4, nullable: true),
        Serializer\Groups(self::GROUP_BACKUP)
    ]
    public ?string $backup_time_code = null {
        set(string|int|null $value) => $this->truncateNullableString(
            Types::stringOrNull($value, true),
            4
        );
    }

    #[
        OA\Property(description: "Whether to exclude media in automated backups.", example: "false"),
        ORM\Column,
        Serializer\Groups(self::GROUP_BACKUP)
    ]
    public bool $backup_exclude_media = false;

    #[
        OA\Property(description: "Number of backups to keep, or infinite if zero/null.", example: 2),
        ORM\Column(type: 'smallint'),
        Serializer\Groups(self::GROUP_BACKUP)
    ]
    public int $backup_keep_copies = 0 {
        set => $this->truncateSmallInt($value);
    }

    #[
        OA\Property(description: "The storage location ID for automated backups.", example: 1),
        ORM\Column(nullable: true),
        Serializer\Groups(self::GROUP_BACKUP)
    ]
    public ?int $backup_storage_location = null {
        set(string|int|null $value) => Types::intOrNull($value);
    }

    #[
        OA\Property(description: "The output format for the automated backup.", example: 'zip'),
        ORM\Column(nullable: true),
        Serializer\Groups(self::GROUP_BACKUP)
    ]
    public ?string $backup_format = null {
        get => Types::stringOrNull($this->backup_format, true);
        set => Types::stringOrNull($value, true);
    }

    #[
        OA\Property(
            description: "The UNIX timestamp when automated backup was last run.",
            example: OpenApi::SAMPLE_TIMESTAMP
        ),
        ORM\Column,
        Attributes\AuditIgnore,
        Serializer\Groups(self::GROUP_BACKUP)
    ]
    public int $backup_last_run = 0;

    public function updateBackupLastRun(): void
    {
        $this->backup_last_run = time();
    }

    #[
        OA\Property(description: "The output of the latest automated backup task.", example: ""),
        ORM\Column(type: 'text', nullable: true),
        Attributes\AuditIgnore,
        Serializer\Groups(self::GROUP_BACKUP)
    ]
    public ?string $backup_last_output = null;

    #[
        OA\Property(
            description: "The UNIX timestamp when setup was last completed.",
            example: OpenApi::SAMPLE_TIMESTAMP
        ),
        ORM\Column
    ]
    public int $setup_complete_time = 0;

    public function isSetupComplete(): bool
    {
        return (0 !== $this->setup_complete_time);
    }

    public function updateSetupComplete(): void
    {
        $this->setup_complete_time = time();
    }

    #[
        OA\Property(description: "Temporarily disable all sync tasks.", example: "false"),
        ORM\Column,
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public bool $sync_disabled = false;

    #[
        OA\Property(
            description: "The last run timestamp for the unified sync task.",
            example: OpenApi::SAMPLE_TIMESTAMP
        ),
        ORM\Column,
        Attributes\AuditIgnore
    ]
    public int $sync_last_run = 0;

    public function updateSyncLastRun(): void
    {
        $this->sync_last_run = time();
    }

    #[
        OA\Property(description: "This installation's external IP.", example: "192.168.1.1"),
        ORM\Column(length: 45, nullable: true),
        Attributes\AuditIgnore
    ]
    public ?string $external_ip = null;

    #[
        OA\Property(description: "The license key for the Maxmind Geolite download.", example: ""),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_GEO_IP)
    ]
    public ?string $geolite_license_key = null {
        get => Types::stringOrNull($this->geolite_license_key, true);
    }

    #[
        OA\Property(
            description: "The UNIX timestamp when the Maxmind Geolite was last downloaded.",
            example: OpenApi::SAMPLE_TIMESTAMP
        ),
        ORM\Column,
        Attributes\AuditIgnore,
        Serializer\Groups(self::GROUP_GEO_IP)
    ]
    public int $geolite_last_run = 0;

    public function updateGeoliteLastRun(): void
    {
        $this->geolite_last_run = time();
    }

    #[
        OA\Property(description: "Enable e-mail delivery across the application.", example: "true"),
        ORM\Column,
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public bool $mail_enabled = false;

    #[
        OA\Property(description: "The name of the sender of system e-mails.", example: "AzuraCast"),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?string $mail_sender_name = '';

    #[
        OA\Property(
            description: "The e-mail address of the sender of system e-mails.",
            example: "example@example.com"
        ),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?string $mail_sender_email = '';

    #[
        OA\Property(description: "The host to send outbound SMTP mail.", example: "smtp.example.com"),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?string $mail_smtp_host = '';

    #[
        OA\Property(description: "The port for sending outbound SMTP mail.", example: 465),
        ORM\Column(type: 'smallint'),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public int $mail_smtp_port = 0;

    #[
        OA\Property(description: "The username when connecting to SMTP mail.", example: "username"),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?string $mail_smtp_username = '' {
        set => $this->truncateNullableString($value);
    }

    #[
        OA\Property(description: "The password when connecting to SMTP mail.", example: "password"),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?string $mail_smtp_password = '';

    #[
        OA\Property(description: "Whether to use a secure (TLS) connection when sending SMTP mail.", example: "true"),
        ORM\Column,
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public bool $mail_smtp_secure = true;

    #[
        OA\Property(description: "The external avatar service to use when fetching avatars.", example: "libravatar"),
        ORM\Column(length: 25, nullable: true),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?string $avatar_service = null;

    #[
        OA\Property(description: "The default avatar URL.", example: ""),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?string $avatar_default_url = null;

    #[
        OA\Property(description: "ACME (LetsEncrypt) e-mail address.", example: ""),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?string $acme_email = null;

    #[
        OA\Property(description: "ACME (LetsEncrypt) domain name(s).", example: ""),
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?string $acme_domains = null {
        get => Types::stringOrNull($this->acme_domains, true);
        set {
            $acmeDomains = Types::stringOrNull($value, true);

            if (null !== $acmeDomains) {
                $acmeDomains = implode(
                    ', ',
                    array_map(
                        static function ($str) {
                            $str = trim($str);
                            $str = trim($str, '/');
                            /** @noinspection HttpUrlsUsage */
                            return str_replace(['http://', 'https://'], '', $str);
                        },
                        explode(',', $acmeDomains)
                    )
                );
            }

            $this->acme_domains = $acmeDomains;
        }
    }

    #[
        OA\Property(description: "IP Address Source"),
        ORM\Column(type: 'string', length: 50, nullable: true, enumType: IpSources::class),
        Serializer\Groups(self::GROUP_GENERAL)
    ]
    public ?IpSources $ip_source = null;

    public function getIp(ServerRequestInterface $request): string
    {
        $ipSource = $this->ip_source ?? IpSources::default();
        return $ipSource->getIp($request);
    }

    public function __toString(): string
    {
        return 'Settings';
    }
}
