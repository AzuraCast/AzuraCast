<?php

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Radio\Adapters;
use App\Radio\Remote\AbstractRemote;
use App\Utilities;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

use const PHP_URL_HOST;
use const PHP_URL_PORT;

/**
 * @ORM\Table(name="station_remotes")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 *
 * @AuditLog\Auditable
 *
 * @OA\Schema(type="object")
 */
class StationRemote implements StationMountInterface
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
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="remotes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="relay_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $relay_id;

    /**
     * @ORM\ManyToOne(targetEntity="Relay", inversedBy="remotes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="relay_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Relay|null
     */
    protected $relay;

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
     * @ORM\Column(name="type", type="string", length=50)
     *
     * @OA\Property(example="icecast")
     * @Assert\Choice(choices={Adapters::REMOTE_ICECAST, Adapters::REMOTE_SHOUTCAST1, Adapters::REMOTE_SHOUTCAST2})
     *
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(name="enable_autodj", type="boolean")
     *
     * @OA\Property(example=false)
     *
     * @var bool
     */
    protected $enable_autodj = false;

    /**
     * @ORM\Column(name="autodj_format", type="string", length=10, nullable=true)
     *
     * @OA\Property(example="mp3")
     *
     * @var string|null
     */
    protected $autodj_format;

    /**
     * @ORM\Column(name="autodj_bitrate", type="smallint", nullable=true)
     *
     * @OA\Property(example=128)
     *
     * @var int|null
     */
    protected $autodj_bitrate;

    /**
     * @ORM\Column(name="custom_listen_url", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="https://custom-listen-url.example.com/stream.mp3")
     *
     * @var string|null
     */
    protected $custom_listen_url;

    /**
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="http://custom-url.example.com")
     *
     * @var string|null
     */
    protected $url;

    /**
     * @ORM\Column(name="mount", type="string", length=150, nullable=true)
     *
     * @OA\Property(example="/stream.mp3")
     *
     * @var string|null
     */
    protected $mount;

    /**
     * @ORM\Column(name="admin_password", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="password")
     *
     * @var string|null
     */
    protected $admin_password;

    /**
     * @ORM\Column(name="source_port", type="smallint", nullable=true, options={"unsigned"=true})
     *
     * @OA\Property(example=8000)
     *
     * @var int|null
     */
    protected $source_port;

    /**
     * @ORM\Column(name="source_mount", type="string", length=150, nullable=true)
     *
     * @OA\Property(example="/")
     *
     * @var string|null
     */
    protected $source_mount;

    /**
     * @ORM\Column(name="source_username", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="source")
     *
     * @var string|null
     */
    protected $source_username;

    /**
     * @ORM\Column(name="source_password", type="string", length=100, nullable=true)
     *
     * @OA\Property(example="password")
     *
     * @var string|null
     */
    protected $source_password;

    /**
     * @ORM\Column(name="is_public", type="boolean")
     *
     * @OA\Property(example=false)
     *
     * @var bool
     */
    protected $is_public = false;

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
        $this->custom_listen_url = $this->truncateString($custom_listen_url, 255);
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
        $this->source_username = $this->truncateString($source_username, 100);
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
        $this->source_password = $this->truncateString($source_password, 100);
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
        $this->source_mount = $this->truncateString($source_mount, 150);
    }

    public function getMount(): ?string
    {
        return $this->mount;
    }

    public function setMount(?string $mount): void
    {
        $this->mount = $this->truncateString($mount, 150);
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
        if (!empty($url) && substr($url, 0, 4) !== 'http') {
            $url = 'http://' . $url;
        }

        $this->url = $this->truncateString($url);
    }

    /*
     * StationMountInterface compliance methods
     */

    /** @inheritdoc */
    public function getAutodjPort(): ?int
    {
        if (!empty($this->getSourcePort())) {
            return $this->getSourcePort();
        }

        return parse_url($this->getUrl(), PHP_URL_PORT);
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

        $response->listeners = new Api\NowPlayingListeners([
            'unique' => $this->listeners_unique,
            'total' => $this->listeners_total,
        ]);

        if ($this->enable_autodj || (Adapters::REMOTE_AZURARELAY === $this->type)) {
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

        return Utilities::truncateUrl($this->url);
    }

    /**
     * @param string|null $display_name
     */
    public function setDisplayName(?string $display_name): void
    {
        $this->display_name = $this->truncateString($display_name);
    }
}
