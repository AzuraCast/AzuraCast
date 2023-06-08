<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'station_queue'),
    ORM\Index(columns: ['is_played'], name: 'idx_is_played'),
    ORM\Index(columns: ['timestamp_played'], name: 'idx_timestamp_played'),
    ORM\Index(columns: ['sent_to_autodj'], name: 'idx_sent_to_autodj'),
    ORM\Index(columns: ['timestamp_cued'], name: 'idx_timestamp_cued')
]
class StationQueue implements
    Interfaces\SongInterface,
    Interfaces\IdentifiableEntityInterface,
    Interfaces\StationAwareInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateInts;
    use Traits\HasSongFields;

    public const DAYS_TO_KEEP = 7;
    public const QUEUE_LOG_TTL = 86400;

    #[ORM\ManyToOne(inversedBy: 'history')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Station $station;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $station_id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?StationPlaylist $playlist = null;

    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    protected ?int $playlist_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?StationMedia $media = null;

    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    protected ?int $media_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?StationRequest $request = null;

    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    protected ?int $request_id = null;

    #[ORM\Column]
    protected bool $sent_to_autodj = false;

    #[ORM\Column]
    protected bool $is_played = false;

    #[ORM\Column]
    protected bool $is_visible = true;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $autodj_custom_uri = null;

    #[ORM\Column]
    protected int $timestamp_cued;

    #[ORM\Column]
    protected int $timestamp_played;

    #[ORM\Column(nullable: true)]
    protected ?int $duration = null;

    public function __construct(Station $station, Interfaces\SongInterface $song)
    {
        $this->setSong($song);
        $this->station = $station;

        $this->timestamp_cued = time();
        $this->timestamp_played = time();
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

    public function setMedia(?StationMedia $media = null): void
    {
        $this->media = $media;

        if (null !== $media) {
            $this->setDuration($media->getCalculatedLength());
        }
    }

    public function getRequest(): ?StationRequest
    {
        return $this->request;
    }

    public function setRequest(?StationRequest $request): void
    {
        $this->request = $request;
    }

    public function getAutodjCustomUri(): ?string
    {
        return $this->autodj_custom_uri;
    }

    public function setAutodjCustomUri(?string $autodjCustomUri): void
    {
        $this->autodj_custom_uri = $autodjCustomUri;
    }

    public function getTimestampCued(): int
    {
        return $this->timestamp_cued;
    }

    public function setTimestampCued(int $timestampCued): void
    {
        $this->timestamp_cued = $timestampCued;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): void
    {
        $this->duration = $duration;
    }

    public function getSentToAutodj(): bool
    {
        return $this->sent_to_autodj;
    }

    public function setSentToAutodj(bool $newValue = true): void
    {
        $this->sent_to_autodj = $newValue;
    }

    public function getIsPlayed(): bool
    {
        return $this->is_played;
    }

    public function setIsPlayed(bool $newValue = true): void
    {
        if ($newValue) {
            $this->sent_to_autodj = true;
            $this->setTimestampPlayed(time());
        }
        $this->is_played = $newValue;
    }

    public function getIsVisible(): bool
    {
        return $this->is_visible;
    }

    public function setIsVisible(bool $isVisible): void
    {
        $this->is_visible = $isVisible;
    }

    public function updateVisibility(): void
    {
        $this->is_visible = !($this->playlist instanceof StationPlaylist) || !$this->playlist->getIsJingle();
    }

    public function getTimestampPlayed(): int
    {
        return $this->timestamp_played;
    }

    public function setTimestampPlayed(int $timestampPlayed): void
    {
        $this->timestamp_played = $timestampPlayed;
    }

    public function __toString(): string
    {
        return (null !== $this->media)
            ? (string)$this->media
            : (string)(new Song($this));
    }

    public static function fromMedia(Station $station, StationMedia $media): self
    {
        $sq = new self($station, $media);
        $sq->setMedia($media);
        return $sq;
    }

    public static function fromRequest(StationRequest $request): self
    {
        $sq = new self($request->getStation(), $request->getTrack());
        $sq->setRequest($request);
        $sq->setMedia($request->getTrack());
        return $sq;
    }
}
