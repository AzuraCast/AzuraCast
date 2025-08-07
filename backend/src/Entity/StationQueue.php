<?php

declare(strict_types=1);

namespace App\Entity;

use App\Utilities\Time;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'station_queue'),
    ORM\Index(name: 'idx_is_played', columns: ['is_played']),
    ORM\Index(name: 'idx_timestamp_played', columns: ['timestamp_played']),
    ORM\Index(name: 'idx_sent_to_autodj', columns: ['sent_to_autodj']),
    ORM\Index(name: 'idx_timestamp_cued', columns: ['timestamp_cued'])
]
final class StationQueue implements
    Interfaces\SongInterface,
    Interfaces\IdentifiableEntityInterface,
    Interfaces\StationAwareInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateInts;
    use Traits\HasSongFields;

    public const int DAYS_TO_KEEP = 7;

    #[ORM\ManyToOne(inversedBy: 'history')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public readonly Station $station;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $station_id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    public ?StationPlaylist $playlist = null;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $playlist_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    public ?StationMedia $media = null {
        set {
            $this->media = $value;

            if (null !== $value) {
                $this->duration = $value->getCalculatedLength();
            }
        }
    }

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $media_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    public ?StationRequest $request = null;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $request_id = null;

    #[ORM\Column]
    public bool $sent_to_autodj = false;

    #[ORM\Column]
    public bool $is_played = false {
        set {
            if ($value) {
                $this->sent_to_autodj = true;
                $this->timestamp_played = Time::nowUtc();
            }

            $this->is_played = $value;
        }
    }

    #[ORM\Column]
    public bool $is_visible = true;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $autodj_custom_uri = null;

    #[ORM\Column(type: 'datetime_immutable', precision: 6)]
    public DateTimeImmutable $timestamp_cued {
        set (DateTimeImmutable|string|null $value) => Time::toUtcCarbonImmutable($value);
    }

    #[ORM\Column(type: 'datetime_immutable', precision: 6, nullable: true)]
    public ?DateTimeImmutable $timestamp_played = null {
        set (DateTimeImmutable|string|null $value) => Time::toNullableUtcCarbonImmutable($value);
    }

    #[ORM\Column(type: 'float', nullable: true)]
    public ?float $duration = null;

    public function __construct(Station $station, Interfaces\SongInterface $song)
    {
        $this->setSong($song);
        $this->station = $station;

        $this->timestamp_cued = Time::nowUtc();
    }

    public function updateVisibility(): void
    {
        $this->is_visible = !($this->playlist instanceof StationPlaylist) || !$this->playlist->is_jingle;
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
        $sq->media = $media;

        return $sq;
    }

    public static function fromRequest(StationRequest $request): self
    {
        $sq = new self($request->station, $request->track);
        $sq->request = $request;
        $sq->media = $request->track;

        return $sq;
    }
}
