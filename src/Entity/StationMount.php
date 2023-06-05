<?php

declare(strict_types=1);

namespace App\Entity;

use App\Radio\Enums\AdapterTypeInterface;
use App\Radio\Enums\FrontendAdapters;
use App\Radio\Enums\StreamFormats;
use App\Radio\Enums\StreamProtocols;
use App\Radio\Frontend\AbstractFrontend;
use App\Utilities\Urls;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UriInterface;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'station_mounts'),
    Attributes\Auditable
]
class StationMount implements
    Stringable,
    Interfaces\StationMountInterface,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;
    use Traits\TruncateInts;

    #[
        ORM\ManyToOne(inversedBy: 'mounts'),
        ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    protected Station $station;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $station_id;

    #[
        OA\Property(example: "/radio.mp3"),
        ORM\Column(length: 100),
        Assert\NotBlank
    ]
    protected string $name = '';

    #[
        OA\Property(example: "128kbps MP3"),
        ORM\Column(length: 255, nullable: true)
    ]
    protected ?string $display_name = null;

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    protected bool $is_visible_on_public_pages = true;

    #[
        OA\Property(example: false),
        ORM\Column
    ]
    protected bool $is_default = false;

    #[
        OA\Property(example: false),
        ORM\Column
    ]
    protected bool $is_public = false;

    #[
        OA\Property(example: "/error.mp3"),
        ORM\Column(length: 100, nullable: true)
    ]
    protected ?string $fallback_mount = null;

    #[
        OA\Property(example: "https://radio.example.com:8000/radio.mp3"),
        ORM\Column(length: 255, nullable: true)
    ]
    protected ?string $relay_url = null;

    #[
        OA\Property(example: ""),
        ORM\Column(length: 255, nullable: true)
    ]
    protected ?string $authhash = null;

    #[
        OA\Property(example: 43200),
        ORM\Column(type: 'integer', nullable: false)
    ]
    protected int $max_listener_duration = 0;

    #[
        OA\Property(example: true),
        ORM\Column
    ]
    protected bool $enable_autodj = true;

    #[
        OA\Property(example: "mp3"),
        ORM\Column(type: 'string', length: 10, nullable: true, enumType: StreamFormats::class)
    ]
    protected ?StreamFormats $autodj_format = StreamFormats::Mp3;

    #[
        OA\Property(example: 128),
        ORM\Column(type: 'smallint', nullable: true)
    ]
    protected ?int $autodj_bitrate = 128;

    #[
        OA\Property(example: "https://custom-listen-url.example.com/stream.mp3"),
        ORM\Column(length: 255, nullable: true)
    ]
    protected ?string $custom_listen_url = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $intro_path = null;

    #[
        OA\Property(type: "array", items: new OA\Items()),
        ORM\Column(type: 'text', nullable: true)
    ]
    protected ?string $frontend_config = null;

    #[
        OA\Property(
            description: "The most recent number of unique listeners.",
            example: 10
        ),
        ORM\Column,
        Attributes\AuditIgnore
    ]
    protected int $listeners_unique = 0;

    #[
        OA\Property(
            description: "The most recent number of total (non-unique) listeners.",
            example: 12
        ),
        ORM\Column,
        Attributes\AuditIgnore
    ]
    protected int $listeners_total = 0;

    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $new_name): void
    {
        // Ensure all mount point names start with a leading slash.
        $this->name = $this->truncateString('/' . ltrim($new_name, '/'), 100);
    }

    public function getDisplayName(): string
    {
        if (!empty($this->display_name)) {
            return $this->display_name;
        }

        if ($this->enable_autodj) {
            $format = $this->getAutodjFormat();

            return (null !== $format)
                ? $this->name . ' (' . $format->formatBitrate($this->autodj_bitrate) . ')'
                : $this->name;
        }

        return $this->name;
    }

    public function setDisplayName(?string $display_name): void
    {
        $this->display_name = $this->truncateNullableString($display_name);
    }

    public function getIsVisibleOnPublicPages(): bool
    {
        return $this->is_visible_on_public_pages;
    }

    public function setIsVisibleOnPublicPages(bool $is_visible_on_public_pages): void
    {
        $this->is_visible_on_public_pages = $is_visible_on_public_pages;
    }

    public function getIsDefault(): bool
    {
        return $this->is_default;
    }

    public function setIsDefault(bool $is_default): void
    {
        $this->is_default = $is_default;
    }

    public function getIsPublic(): bool
    {
        return $this->is_public;
    }

    public function setIsPublic(bool $is_public): void
    {
        $this->is_public = $is_public;
    }

    public function getFallbackMount(): ?string
    {
        return $this->fallback_mount;
    }

    public function setFallbackMount(?string $fallback_mount = null): void
    {
        $this->fallback_mount = $fallback_mount;
    }

    public function getRelayUrl(): ?string
    {
        return $this->relay_url;
    }

    public function getRelayUrlAsUri(): ?UriInterface
    {
        $relayUri = Urls::tryParseUserUrl(
            $this->relay_url,
            'Mount Point ' . $this->__toString() . ' Relay URL'
        );

        if (null !== $relayUri) {
            // Relays need port explicitly provided.
            $port = $relayUri->getPort();
            if ($port === null && '' !== $relayUri->getScheme()) {
                $relayUri = $relayUri->withPort(
                    ('https' === $relayUri->getScheme()) ? 443 : 80
                );
            }
        }

        return $relayUri;
    }

    public function setRelayUrl(?string $relay_url = null): void
    {
        $this->relay_url = $this->truncateNullableString($relay_url);
    }

    public function getAuthhash(): ?string
    {
        return $this->authhash;
    }

    public function setAuthhash(?string $authhash = null): void
    {
        $this->authhash = $this->truncateNullableString($authhash);
    }

    public function getMaxListenerDuration(): int
    {
        return $this->max_listener_duration;
    }

    public function setMaxListenerDuration(int $max_listener_duration): void
    {
        $this->max_listener_duration = $this->truncateInt($max_listener_duration);
    }

    public function getEnableAutodj(): bool
    {
        return $this->enable_autodj;
    }

    public function setEnableAutodj(bool $enable_autodj): void
    {
        $this->enable_autodj = $enable_autodj;
    }

    public function getAutodjFormat(): ?StreamFormats
    {
        return $this->autodj_format;
    }

    public function setAutodjFormat(?StreamFormats $autodj_format = null): void
    {
        $this->autodj_format = $autodj_format;
    }

    public function getAutodjBitrate(): ?int
    {
        return $this->autodj_bitrate;
    }

    public function setAutodjBitrate(?int $autodj_bitrate = null): void
    {
        $this->autodj_bitrate = $autodj_bitrate;
    }

    public function getCustomListenUrl(): ?string
    {
        return $this->custom_listen_url;
    }

    public function getCustomListenUrlAsUri(): ?UriInterface
    {
        return Urls::tryParseUserUrl(
            $this->custom_listen_url,
            'Mount Point ' . $this->__toString() . ' Listen URL'
        );
    }

    public function setCustomListenUrl(?string $custom_listen_url = null): void
    {
        $this->custom_listen_url = $this->truncateNullableString($custom_listen_url);
    }

    public function getFrontendConfig(): ?string
    {
        return $this->frontend_config;
    }

    public function setFrontendConfig(?string $frontend_config = null): void
    {
        $this->frontend_config = $frontend_config;
    }

    public function getListenersUnique(): int
    {
        return $this->listeners_unique;
    }

    public function setListenersUnique(int $listeners_unique): void
    {
        $this->listeners_unique = $listeners_unique;
    }

    public function getListenersTotal(): int
    {
        return $this->listeners_total;
    }

    public function setListenersTotal(int $listeners_total): void
    {
        $this->listeners_total = $listeners_total;
    }

    public function getIntroPath(): ?string
    {
        return $this->intro_path;
    }

    public function setIntroPath(?string $intro_path): void
    {
        $this->intro_path = $intro_path;
    }

    public function getAutodjHost(): ?string
    {
        return '127.0.0.1';
    }

    public function getAutodjPort(): ?int
    {
        return $this->getStation()->getFrontendConfig()->getPort();
    }

    public function getAutodjProtocol(): ?StreamProtocols
    {
        return match ($this->getAutodjAdapterType()) {
            FrontendAdapters::Shoutcast => StreamProtocols::Icy,
            default => null
        };
    }

    public function getAutodjUsername(): ?string
    {
        return '';
    }

    public function getAutodjPassword(): ?string
    {
        return $this->getStation()->getFrontendConfig()->getSourcePassword();
    }

    public function getAutodjMount(): ?string
    {
        return $this->getName();
    }

    public function getAutodjAdapterType(): AdapterTypeInterface
    {
        return $this->getStation()->getFrontendType();
    }

    public function getIsShoutcast(): bool
    {
        return match ($this->getAutodjAdapterType()) {
            FrontendAdapters::Shoutcast => true,
            default => false
        };
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param AbstractFrontend $fa
     * @param UriInterface|null $base_url
     */
    public function api(
        AbstractFrontend $fa,
        UriInterface $base_url = null
    ): Api\NowPlaying\StationMount {
        $response = new Api\NowPlaying\StationMount();

        $response->id = $this->getIdRequired();
        $response->name = $this->getDisplayName();
        $response->path = $this->getName();
        $response->is_default = $this->is_default;
        $response->url = $fa->getUrlForMount($this->station, $this, $base_url);

        $response->listeners = new Api\NowPlaying\Listeners(
            total: $this->listeners_total,
            unique: $this->listeners_unique
        );

        if ($this->enable_autodj) {
            $response->bitrate = (int)$this->autodj_bitrate;
            $response->format = $this->autodj_format?->value;
        }

        return $response;
    }

    public function __toString(): string
    {
        return $this->getStation() . ' Mount: ' . $this->getDisplayName();
    }
}
