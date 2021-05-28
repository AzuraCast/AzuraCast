<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'station_queue')
]
class StationQueue implements SongInterface
{
    use Traits\TruncateInts;
    use Traits\HasSongFields;

    #[ORM\Column]
    #[ORM\Id, ORM\GeneratedValue]
    protected int $id;

    #[ORM\Column]
    protected int $station_id;

    #[ORM\ManyToOne(inversedBy: 'history')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Station $station;

    #[ORM\Column]
    protected ?int $playlist_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?StationPlaylist $playlist = null;

    #[ORM\Column]
    protected ?int $media_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?StationMedia $media = null;

    #[ORM\Column]
    protected ?int $request_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?StationRequest $request = null;

    #[ORM\Column]
    protected bool $sent_to_autodj = false;

    #[ORM\Column(length: 255)]
    protected ?string $autodj_custom_uri = null;

    #[ORM\Column]
    protected int $timestamp_cued;

    #[ORM\Column]
    protected ?int $duration = null;

    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $log;

    public function __construct(Station $station, SongInterface $song)
    {
        $this->setSong($song);
        $this->station = $station;
    }

    public function getId(): ?int
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
        $this->setTimestampCued(time());
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
