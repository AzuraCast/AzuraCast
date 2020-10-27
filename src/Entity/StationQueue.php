<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="station_queue")
 * @ORM\Entity()
 */
class StationQueue implements SongInterface
{
    use Traits\TruncateInts;
    use Traits\HasSongFields;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="history")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="playlist_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $playlist_id;

    /**
     * @ORM\ManyToOne(targetEntity="StationPlaylist")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="playlist_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationPlaylist|null
     */
    protected $playlist;

    /**
     * @ORM\Column(name="media_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $media_id;

    /**
     * @ORM\ManyToOne(targetEntity="StationMedia")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="media_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationMedia|null
     */
    protected $media;

    /**
     * @ORM\Column(name="request_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $request_id;

    /**
     * @ORM\ManyToOne(targetEntity="StationRequest")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="request_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationRequest|null
     */
    protected $request;

    /**
     * @ORM\Column(name="sent_to_autodj", type="boolean")
     * @var bool
     */
    protected $sent_to_autodj;

    /**
     * @ORM\Column(name="autodj_custom_uri", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $autodj_custom_uri;

    /**
     * @ORM\Column(name="timestamp_cued", type="integer")
     * @var int
     */
    protected $timestamp_cued;

    /**
     * @ORM\Column(name="duration", type="integer", nullable=true)
     * @var int|null
     */
    protected $duration;

    /**
     * @ORM\Column(name="log", type="json", nullable=true)
     * @var array|null Any relevant logs regarding how this track was selected.
     */
    protected $log;

    public function __construct(Station $station, SongInterface $song)
    {
        $this->setSong($song);
        $this->station = $station;

        $this->sent_to_autodj = false;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getPlaylist(): ?StationPlaylist
    {
        return $this->playlist;
    }

    public function setPlaylist(StationPlaylist $playlist = null): void
    {
        $this->playlist = $playlist;
    }

    public function getMedia(): ?StationMedia
    {
        return $this->media;
    }

    public function setMedia(StationMedia $media = null): void
    {
        $this->media = $media;

        if ($media instanceof StationMedia) {
            $this->setDuration($media->getCalculatedLength());
        }
    }

    public function getRequest(): ?StationRequest
    {
        return $this->request;
    }

    public function setRequest($request): void
    {
        $this->request = $request;
    }

    public function getAutodjCustomUri(): ?string
    {
        return $this->autodj_custom_uri;
    }

    public function setAutodjCustomUri(?string $autodj_custom_uri): void
    {
        $this->autodj_custom_uri = $autodj_custom_uri;
    }

    public function getTimestampCued(): int
    {
        return $this->timestamp_cued;
    }

    public function setTimestampCued(int $timestamp_cued): void
    {
        $this->timestamp_cued = $timestamp_cued;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): void
    {
        $this->duration = $duration;
    }

    public function isSentToAutoDj(): bool
    {
        return $this->sent_to_autodj;
    }

    public function sentToAutoDj(): void
    {
        $cued = $this->getTimestampCued();
        if (0 === $cued) {
            $this->setTimestampCued(time());
        }

        $this->sent_to_autodj = true;
    }

    /**
     * @return string[]|null
     */
    public function getLog(): ?array
    {
        return $this->log;
    }

    public function setLog(?array $log): void
    {
        $this->log = $log;
    }

    /**
     * @return bool Whether the record should be shown in APIs (i.e. is not a jingle)
     */
    public function showInApis(): bool
    {
        if ($this->playlist instanceof StationPlaylist) {
            return !$this->playlist->isJingle();
        }
        return true;
    }

    public function __toString(): string
    {
        return (null !== $this->media)
            ? (string)$this->media
            : (string)(new Song($this));
    }
}
