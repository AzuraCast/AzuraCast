<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Entity\Interfaces\StationMountInterface;
use App\Radio\Adapters;
use App\Radio\Remote\AbstractRemote;
use App\Utilities;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

use const PHP_URL_HOST;
use const PHP_URL_PORT;

/** @OA\Schema(type="object") */
#[
    ORM\Entity,
    ORM\Table(name: 'station_remotes'),
    AuditLog\Auditable
]
class StationRemote implements StationMountInterface, Stringable
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[ORM\Column]
    protected int $station_id;

    #[ORM\ManyToOne(inversedBy: 'remotes')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Station $station;

    #[ORM\Column]
    protected ?int $relay_id = null;

    #[ORM\ManyToOne(inversedBy: 'remotes')]
    #[ORM\JoinColumn(name: 'relay_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Relay $relay = null;

    /** @OA\Property(example="128kbps MP3") */
    #[ORM\Column(length: 255)]
    protected ?string $display_name = null;

    /** @OA\Property(example=true) */
    #[ORM\Column]
    protected bool $is_visible_on_public_pages = true;

    /** @OA\Property(example="icecast") */
    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: [Adapters::REMOTE_ICECAST, Adapters::REMOTE_SHOUTCAST1, Adapters::REMOTE_SHOUTCAST2])]
    protected string $type;

    /** @OA\Property(example=false) */
    #[ORM\Column]
    protected bool $enable_autodj = false;

    /** @OA\Property(example="mp3") */
    #[ORM\Column(length: 10)]
    protected ?string $autodj_format = null;

    /** @OA\Property(example=128) */
    #[ORM\Column(type: 'smallint')]
    protected ?int $autodj_bitrate = null;

    /** @OA\Property(example="https://custom-listen-url.example.com/stream.mp3") */
    #[ORM\Column(length: 255)]
    protected ?string $custom_listen_url = null;

    /** @OA\Property(example="http://custom-url.example.com") */
    #[ORM\Column(length: 255)]
    protected ?string $url = null;

    /** @OA\Property(example="/stream.mp3") */
    #[ORM\Column(length: 150)]
    protected ?string $mount = null;

    /** @OA\Property(example="password") */
    #[ORM\Column(length: 100)]
    protected ?string $admin_password = null;

    /** @OA\Property(example=8000) */
    #[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
    protected ?int $source_port = null;

    /** @OA\Property(example="/") */
    #[ORM\Column(length: 150)]
    protected ?string $source_mount = null;

    /** @OA\Property(example="source") */
    #[ORM\Column(length: 100)]
    protected ?string $source_username = null;

    /** @OA\Property(example="password") */
    #[ORM\Column(length: 100)]
    protected ?string $source_password = null;

    /** @OA\Property(example=false) */
    #[ORM\Column]
    protected bool $is_public = false;

    /**
     * @OA\Property(
     *     description="The most recent number of unique listeners.",
     *     example=10
     * )
     */
    #[ORM\Column]
    #[AuditLog\AuditIgnore]
    protected int $listeners_unique = 0;

    /**
     * @OA\Property(
     *     description="The most recent number of total (non-unique) listeners.",
     *     example=12
     * )
     */
    #[ORM\Column]
    #[AuditLog\AuditIgnore]
    protected int $listeners_total = 0;

    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getRelay(): ?Relay
    {
        return $this->relay;
    }

    public function setRelay(?Relay $relay): void
    {
        $this->relay = $relay;
    }

    public function isVisibleOnPublicPages(): bool
    {
        return $this->is_visible_on_public_pages;
    }

    public function setIsVisibleOnPublicPages(bool $is_visible_on_public_pages): void
    {
        $this->is_visible_on_public_pages = $is_visible_on_public_pages;
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

    public function setAutodjFormat(string $autodj_format = null): void
    {
        $this->autodj_format = $autodj_format;
    }

    public function getAutodjBitrate(): ?int
    {
        return $this->autodj_bitrate;
    }

    public function setAutodjBitrate(int $autodj_bitrate = null): void
    {
        $this->autodj_bitrate = $autodj_bitrate;
    }

    public function getCustomListenUrl(): ?string
    {
        return $this->custom_listen_url;
    }

    public function setCustomListenUrl(string $custom_listen_url = null): void
    {
        $this->custom_listen_url = $this->truncateString($custom_listen_url);
    }

    /** @inheritdoc */
    public function getAutodjUsername(): ?string
    {
        return $this->getSourceUsername();
    }

    public function getSourceUsername(): ?string
    {
        return $this->source_username;
    }

    public function setSourceUsername(?string $source_username): void
    {
        $this->source_username = $this->truncateNullableString($source_username, 100);
    }

    /** @inheritdoc */
    public function getAutodjPassword(): ?string
    {
        $password = $this->getSourcePassword();

        if (Adapters::REMOTE_SHOUTCAST2 === $this->getType()) {
            $mount = $this->getSourceMount();
            if (empty($mount)) {
                $mount = $this->getMount();
            }

            if (!empty($mount)) {
                $password .= ':#' . $mount;
            }
        }

        return $password;
    }

    public function getSourcePassword(): ?string
    {
        return $this->source_password;
    }

    public function setSourcePassword(?string $source_password): void
    {
        $this->source_password = $this->truncateNullableString($source_password, 100);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getSourceMount(): ?string
    {
        return $this->source_mount;
    }

    public function setSourceMount(?string $source_mount): void
    {
        $this->source_mount = $this->truncateNullableString($source_mount, 150);
    }

    public function getMount(): ?string
    {
        return $this->mount;
    }

    public function setMount(?string $mount): void
    {
        $this->mount = $this->truncateNullableString($mount, 150);
    }

    public function getAdminPassword(): ?string
    {
        return $this->admin_password;
    }

    public function setAdminPassword(?string $admin_password): void
    {
        $this->admin_password = $admin_password;
    }

    /** @inheritdoc */
    public function getAutodjMount(): ?string
    {
        if (Adapters::REMOTE_ICECAST !== $this->getType()) {
            return null;
        }

        $mount = $this->getSourceMount();
        if (!empty($mount)) {
            return $mount;
        }

        return $this->getMount();
    }

    /** @inheritdoc */
    public function getAutodjHost(): ?string
    {
        return parse_url($this->getUrl(), PHP_URL_HOST);
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        if (!empty($url) && !str_starts_with($url, 'http')) {
            $url = 'http://' . $url;
        }

        $this->url = $this->truncateNullableString($url);
    }

    /*
     * StationMountInterface compliance methods
     */

    /** @inheritdoc */
    public function getAutodjPort(): ?int
    {
        return $this->getSourcePort() ?? parse_url($this->getUrl(), PHP_URL_PORT);
    }

    public function getSourcePort(): ?int
    {
        return $this->source_port;
    }

    public function setSourcePort(?int $source_port): void
    {
        if ((int)$source_port === 0) {
            $source_port = null;
        }

        $this->source_port = $source_port;
    }

    public function getAutodjProtocol(): ?string
    {
        if (Adapters::REMOTE_SHOUTCAST2 === $this->getAutodjAdapterType()) {
            return self::PROTOCOL_ICY;
        }

        $urlScheme = parse_url($this->getUrl(), PHP_URL_SCHEME);
        return ('https' === $urlScheme)
            ? self::PROTOCOL_HTTPS
            : self::PROTOCOL_HTTP;
    }

    public function getAutodjAdapterType(): string
    {
        return $this->getType();
    }

    public function getIsPublic(): bool
    {
        return $this->is_public;
    }

    public function setIsPublic(bool $is_public): void
    {
        $this->is_public = $is_public;
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

    /**
     * @return bool Whether this remote relay can be hand-edited.
     */
    public function isEditable(): bool
    {
        return (Adapters::REMOTE_AZURARELAY !== $this->type);
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param AbstractRemote $adapter
     */
    public function api(
        AbstractRemote $adapter
    ): Api\StationRemote {
        $response = new Api\StationRemote();

        $response->id = $this->id;
        $response->name = $this->getDisplayName();
        $response->url = $adapter->getPublicUrl($this);

        $response->listeners = new Api\NowPlayingListeners(
            total: $this->listeners_total,
            unique: $this->listeners_unique
        );

        if ($this->enable_autodj || (Adapters::REMOTE_AZURARELAY === $this->type)) {
            $response->bitrate = (int)$this->autodj_bitrate;
            $response->format = (string)$this->autodj_format;
        }

        return $response;
    }

    public function getDisplayName(): string
    {
        if (!empty($this->display_name)) {
            return $this->display_name;
        }

        if ($this->enable_autodj) {
            return $this->autodj_bitrate . 'kbps ' . strtoupper($this->autodj_format);
        }

        return Utilities\Strings::truncateUrl($this->url);
    }

    /**
     * @param string|null $display_name
     */
    public function setDisplayName(?string $display_name): void
    {
        $this->display_name = $this->truncateNullableString($display_name);
    }

    public function __toString(): string
    {
        return (string)$this->getStation() . ' Relay: ' . $this->getDisplayName();
    }
}
