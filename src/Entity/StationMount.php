<?php

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Radio\Adapters;
use App\Radio\Frontend\AbstractFrontend;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="station_mounts")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 *
 * @AuditLog\Auditable
 *
 * @OA\Schema(type="object")
 */
class StationMount implements StationMountInterface
{
    use Traits\TruncateStrings;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @OA\Property(example=1)
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="mounts")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="name", type="string", length=100)
     *
     * @OA\Property(example="/radio.mp3")
     * @Assert\NotBlank()
     *
     * @var string
     */
    protected $name = '';

    /**
     * @ORM\Column(name="display_name", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="128kbps MP3")
     *
     * @var string|null
     */
    protected $display_name;

    /**
     * @ORM\Column(name="is_visible_on_public_pages", type="boolean")
     *
     * @OA\Property(example=true)
     *
     * @var bool
     */
    protected $is_visible_on_public_pages = true;

    /**
     * @ORM\Column(name="is_default", type="boolean")
     *
     * @OA\Property(example=false)
     *
     * @var bool
     */
    protected $is_default = false;

    /**
     * @ORM\Column(name="is_public", type="boolean")
     *
     * @OA\Property(example=false)
     *
     * @var bool
     */
    protected $is_public = false;

    /**
     * @ORM\Column(name="fallback_mount", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="/error.mp3")
     *
     * @var string|null
     */
    protected $fallback_mount;

    /**
     * @ORM\Column(name="relay_url", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="http://radio.example.com:8000/radio.mp3")
     *
     * @var string|null
     */
    protected $relay_url;

    /**
     * @ORM\Column(name="authhash", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="")
     *
     * @var string|null
     */
    protected $authhash;

    /**
     * @ORM\Column(name="enable_autodj", type="boolean")
     *
     * @OA\Property(example=true)
     *
     * @var bool
     */
    protected $enable_autodj = true;

    /**
     * @ORM\Column(name="autodj_format", type="string", length=10, nullable=true)
     *
     * @OA\Property(example="mp3")
     *
     * @var string|null
     */
    protected $autodj_format = 'mp3';

    /**
     * @ORM\Column(name="autodj_bitrate", type="smallint", nullable=true)
     *
     * @OA\Property(example=128)
     *
     * @var int|null
     */
    protected $autodj_bitrate = 128;

    /**
     * @ORM\Column(name="custom_listen_url", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="https://custom-listen-url.example.com/stream.mp3")
     *
     * @var string|null
     */
    protected $custom_listen_url;

    /**
     * @ORM\Column(name="frontend_config", type="text", nullable=true)
     *
     * @OA\Property(@OA\Items())
     *
     * @var string|null
     */
    protected $frontend_config;

    /**
     * @ORM\Column(name="listeners_unique", type="integer")
     * @AuditLog\AuditIgnore
     * @OA\Property(example=10)
     *
     * @var int The most recent number of unique listeners.
     */
    protected $listeners_unique = 0;

    /**
     * @ORM\Column(name="listeners_total", type="integer")
     * @AuditLog\AuditIgnore
     * @OA\Property(example=12)
     *
     * @var int The most recent number of total (non-unique) listeners.
     */
    protected $listeners_total = 0;

    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isVisibleOnPublicPages(): bool
    {
        if ($this->is_default) {
            return true;
        }

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

    public function setRelayUrl(?string $relay_url = null): void
    {
        $this->relay_url = $this->truncateString($relay_url);
    }

    public function getAuthhash(): ?string
    {
        return $this->authhash;
    }

    public function setAuthhash(?string $authhash = null): void
    {
        $this->authhash = $this->truncateString($authhash);
    }

    public function getEnableAutodj(): bool
    {
        return $this->enable_autodj;
    }

    public function setEnableAutodj(bool $enable_autodj): void
    {
        $this->enable_autodj = $enable_autodj;
    }

    public function getAutodjFormat(): ?string
    {
        return $this->autodj_format;
    }

    public function setAutodjFormat(?string $autodj_format = null): void
    {
        $this->autodj_format = $this->truncateString($autodj_format, 10);
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

    public function setCustomListenUrl(?string $custom_listen_url = null): void
    {
        $this->custom_listen_url = $this->truncateString($custom_listen_url);
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

    /** @inheritdoc */
    public function getAutodjHost(): ?string
    {
        return '127.0.0.1';
    }

    /** @inheritdoc */
    public function getAutodjPort(): ?int
    {
        $fe_settings = $this->getStation()->getFrontendConfig();
        return $fe_settings->getPort();
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    /** @inheritdoc */
    public function getAutodjUsername(): ?string
    {
        return '';
    }

    /** @inheritdoc */
    public function getAutodjPassword(): ?string
    {
        $fe_settings = $this->getStation()->getFrontendConfig();
        return $fe_settings->getSourcePassword();
    }

    /** @inheritdoc */
    public function getAutodjMount(): ?string
    {
        return $this->getName();
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

    public function getAutodjAdapterType(): string
    {
        $adapterLookup = [
            Adapters::FRONTEND_ICECAST => Adapters::REMOTE_ICECAST,
            Adapters::FRONTEND_SHOUTCAST => Adapters::REMOTE_SHOUTCAST2,
        ];

        $frontendType = $this->getStation()->getFrontendType();
        return $adapterLookup[$frontendType];
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
    ): Api\StationMount {
        $response = new Api\StationMount();

        $response->id = $this->id;
        $response->name = $this->getDisplayName();
        $response->path = $this->getName();
        $response->is_default = (bool)$this->is_default;
        $response->url = $fa->getUrlForMount($this->station, $this, $base_url);

        $response->listeners = new Api\NowPlayingListeners([
            'unique' => $this->listeners_unique,
            'total' => $this->listeners_total,
        ]);

        if ($this->enable_autodj) {
            $response->bitrate = (int)$this->autodj_bitrate;
            $response->format = (string)$this->autodj_format;
        }

        return $response;
    }

    /**
     * @AuditLog\AuditIdentifier
     */
    public function getDisplayName(): string
    {
        if (!empty($this->display_name)) {
            return $this->display_name;
        }

        if ($this->enable_autodj) {
            return $this->autodj_bitrate . 'kbps ' . strtoupper($this->autodj_format);
        }

        return $this->name;
    }

    public function setDisplayName(?string $display_name): void
    {
        $this->display_name = $this->truncateString($display_name);
    }
}
