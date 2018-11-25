<?php
namespace App\Entity;

use App\Radio\Adapters;
use App\Radio\Remote\RemoteAbstract;

/**
 * @Table(name="station_remotes")
 * @Entity()
 * @HasLifecycleCallbacks
 */
class StationRemote implements StationMountInterface
{
    use Traits\TruncateStrings;

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="remotes")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @Column(name="type", type="string", length=50)
     * @var string
     */
    protected $type;

    /**
     * @Column(name="enable_autodj", type="boolean")
     * @var bool
     */
    protected $enable_autodj = false;

    /**
     * @Column(name="autodj_format", type="string", length=10, nullable=true)
     * @var string|null
     */
    protected $autodj_format;

    /**
     * @Column(name="autodj_bitrate", type="smallint", nullable=true)
     * @var int|null
     */
    protected $autodj_bitrate;

    /**
     * @Column(name="custom_listen_url", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $custom_listen_url;

    /**
     * @Column(name="url", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $url;

    /**
     * @Column(name="mount", type="string", length=150, nullable=true)
     * @var string|null
     */
    protected $mount;

    /**
     * @Column(name="source_port", type="smallint", nullable=true)
     * @var int|null
     */
    protected $source_port;

    /**
     * @Column(name="source_mount", type="string", length=150, nullable=true)
     * @var string|null
     */
    protected $source_mount;

    /**
     * @Column(name="source_username", type="string", length=100, nullable=true)
     * @var string|null
     */
    protected $source_username;

    /**
     * @Column(name="source_password", type="string", length=100, nullable=true)
     * @var string|null
     */
    protected $source_password;

    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return null|string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param null|string $url
     */
    public function setUrl(?string $url): void
    {
        if (!empty($url)) {
            if (substr($url, 0, 4) !== 'http') {
                $url = 'http://'.$url;
            }
        }

        $this->url = $this->_truncateString($url);
    }

    /**
     * @return null|string
     */
    public function getMount(): ?string
    {
        return $this->mount;
    }

    /**
     * @param null|string $mount
     */
    public function setMount(?string $mount): void
    {
        $this->mount = $this->_truncateString($mount, 150);
    }

    /**
     * @return int|null
     */
    public function getSourcePort(): ?int
    {
        return $this->source_port;
    }

    /**
     * @param int|null $source_port
     */
    public function setSourcePort(?int $source_port): void
    {
        if ((int)$source_port === 0) {
            $source_port = null;
        }

        $this->source_port = $source_port;
    }

    /**
     * @return null|string
     */
    public function getSourceMount(): ?string
    {
        return $this->source_mount;
    }

    /**
     * @param null|string $source_mount
     */
    public function setSourceMount(?string $source_mount): void
    {
        $this->source_mount =  $this->_truncateString($source_mount, 150);
    }

    /**
     * @return null|string
     */
    public function getSourceUsername(): ?string
    {
        return $this->source_username;
    }

    /**
     * @param null|string $source_username
     */
    public function setSourceUsername(?string $source_username): void
    {
        $this->source_username = $this->_truncateString($source_username, 100);
    }

    /**
     * @return null|string
     */
    public function getSourcePassword(): ?string
    {
        return $this->source_password;
    }

    /**
     * @param null|string $source_password
     */
    public function setSourcePassword(?string $source_password): void
    {
        $this->source_password = $this->_truncateString($source_password, 100);
    }

    /**
     * @return bool
     */
    public function getEnableAutodj(): bool
    {
        return $this->enable_autodj;
    }

    /**
     * @param bool $enable_autodj
     */
    public function setEnableAutodj(bool $enable_autodj): void
    {
        $this->enable_autodj = $enable_autodj;
    }

    /**
     * @return null|string
     */
    public function getAutodjFormat(): ?string
    {
        return $this->autodj_format;
    }

    /**
     * @param null|string $autodj_format
     */
    public function setAutodjFormat(string $autodj_format = null): void
    {
        $this->autodj_format = $autodj_format;
    }

    /**
     * @return int|null
     */
    public function getAutodjBitrate(): ?int
    {
        return $this->autodj_bitrate;
    }

    /**
     * @param int|null $autodj_bitrate
     */
    public function setAutodjBitrate(int $autodj_bitrate = null): void
    {
        $this->autodj_bitrate = $autodj_bitrate;
    }

    /**
     * @return string|null
     */
    public function getCustomListenUrl(): ?string
    {
        return $this->custom_listen_url;
    }

    /**
     * @param null|string $custom_listen_url
     */
    public function setCustomListenUrl(string $custom_listen_url = null): void
    {
        $this->custom_listen_url = $this->_truncateString($custom_listen_url, 255);
    }

    /*
     * StationMountInterface compliance methods
     */

    /** @inheritdoc */
    public function getAutodjUsername(): ?string
    {
        return $this->getSourceUsername();
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
                $password .= ':#'.$mount;
            }
        }

        return $password;
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
        return parse_url($this->getUrl(), \PHP_URL_HOST);
    }

    /** @inheritdoc */
    public function getAutodjPort(): ?int
    {
        if (!empty($this->getSourcePort())) {
            return $this->getSourcePort();
        }

        return parse_url($this->getUrl(), \PHP_URL_PORT);
    }

    /** @inheritdoc */
    public function getAutodjShoutcastMode(): bool
    {
        return (Adapters::REMOTE_ICECAST !== $this->getType());
    }

    /** @inheritdoc */
    public function getIsPublic(): bool
    {
        return false;
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param RemoteAbstract $adapter
     * @return Api\StationRemote
     */
    public function api(RemoteAbstract $adapter): Api\StationRemote
    {
        $response = new Api\StationRemote;
        $response->url = $adapter->getPublicUrl($this);

        if ($this->enable_autodj) {
            $response->bitrate = (int)$this->autodj_bitrate;
            $response->format = (string)$this->autodj_format;
        }

        return $response;
    }
}
