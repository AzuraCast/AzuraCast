<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Radio\Adapters;
use App\Radio\Frontend\AbstractFrontend;
use Psr\Http\Message\UriInterface;

/**
 * @ORM\Table(name="station_mounts")
 * @ORM\Entity(repositoryClass="App\Entity\Repository\StationMountRepository")
 * @ORM\HasLifecycleCallbacks
 */
class StationMount implements StationMountInterface
{
    use Traits\TruncateStrings;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
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
     * @var string
     */
    protected $name = '';

    /**
     * @ORM\Column(name="is_default", type="boolean")
     * @var bool
     */
    protected $is_default = false;

    /**
     * @ORM\Column(name="is_public", type="boolean")
     * @var bool
     */
    protected $is_public = false;

    /**
     * @ORM\Column(name="fallback_mount", type="string", length=100, nullable=true)
     * @var string|null
     */
    protected $fallback_mount;

    /**
     * @ORM\Column(name="relay_url", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $relay_url;

    /**
     * @ORM\Column(name="authhash", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $authhash;

    /**
     * @ORM\Column(name="enable_autodj", type="boolean")
     * @var bool
     */
    protected $enable_autodj = true;

    /**
     * @ORM\Column(name="autodj_format", type="string", length=10, nullable=true)
     * @var string|null
     */
    protected $autodj_format = 'mp3';

    /**
     * @ORM\Column(name="autodj_bitrate", type="smallint", nullable=true)
     * @var int|null
     */
    protected $autodj_bitrate = 128;

    /**
     * @ORM\Column(name="custom_listen_url", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $custom_listen_url;

    /**
     * @ORM\Column(name="frontend_config", type="text", nullable=true)
     * @var string|null
     */
    protected $frontend_config;

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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $new_name
     */
    public function setName(string $new_name): void
    {
        // Ensure all mount point names start with a leading slash.
        $this->name = $this->_truncateString('/' . ltrim($new_name, '/'), 100);
    }

    /**
     * @return bool
     */
    public function getIsDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * @param bool $is_default
     */
    public function setIsDefault(bool $is_default): void
    {
        $this->is_default = $is_default;
    }

    /**
     * @return bool
     */
    public function getIsPublic(): bool
    {
        return $this->is_public;
    }

    /**
     * @param bool $is_public
     */
    public function setIsPublic(bool $is_public): void
    {
        $this->is_public = $is_public;
    }

    /**
     * @return null|string
     */
    public function getFallbackMount(): ?string
    {
        return $this->fallback_mount;
    }

    /**
     * @param null|string $fallback_mount
     */
    public function setFallbackMount($fallback_mount): void
    {
        $this->fallback_mount = $fallback_mount;
    }

    /**
     * @return mixed
     */
    public function getRelayUrl()
    {
        return $this->relay_url;
    }

    /**
     * @param null|string $relay_url
     */
    public function setRelayUrl($relay_url): void
    {
        $this->relay_url = $relay_url;
    }

    /**
     * @return string|null
     */
    public function getAuthhash(): ?string
    {
        return $this->authhash;
    }

    /**
     * @param null|string $authhash
     */
    public function setAuthhash(string $authhash = null): void
    {
        $this->authhash = $authhash;
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
        $this->custom_listen_url = $custom_listen_url;
    }

    /**
     * @return string|null
     */
    public function getFrontendConfig(): ?string
    {
        return $this->frontend_config;
    }

    /**
     * @param null|string $frontend_config
     */
    public function setFrontendConfig(string $frontend_config = null): void
    {
        $this->frontend_config = $frontend_config;
    }

    /*
     * StationMountInterface compliance methods
     */

    /** @inheritdoc */
    public function getAutodjHost(): ?string
    {
        return '127.0.0.1';
    }

    /** @inheritdoc */
    public function getAutodjPort(): ?int
    {
        $fe_settings = (array)$this->getStation()->getFrontendConfig();
        return $fe_settings['port'];
    }

    /** @inheritdoc */
    public function getAutodjUsername(): ?string
    {
        return '';
    }

    /** @inheritdoc */
    public function getAutodjPassword(): ?string
    {
        $fe_settings = (array)$this->getStation()->getFrontendConfig();
        return $fe_settings['source_pw'];
    }

    /** @inheritdoc */
    public function getAutodjMount(): ?string
    {
        return $this->getName();
    }

    /** @inheritdoc */
    public function getAutodjShoutcastMode(): bool
    {
        return (Adapters::FRONTEND_SHOUTCAST === $this->getStation()->getFrontendType());
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param AbstractFrontend $fa
     * @param UriInterface|null $base_url
     * @return Api\StationMount
     */
    public function api(AbstractFrontend $fa, UriInterface $base_url = null): Api\StationMount
    {
        $response = new Api\StationMount;

        $response->name = (string)$this->name;
        $response->is_default = (bool)$this->is_default;
        $response->url = $fa->getUrlForMount($this->station, $this, $base_url);

        if ($this->enable_autodj) {
            $response->bitrate = (int)$this->autodj_bitrate;
            $response->format = (string)$this->autodj_format;
        }

        return $response;
    }
}
