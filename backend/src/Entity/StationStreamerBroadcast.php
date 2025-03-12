<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Utilities\Time;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;

#[
    OA\Schema(
        description: 'Each individual broadcast associated with a streamer.',
        type: "object"
    ),
    ORM\Entity,
    ORM\Table(name: 'station_streamer_broadcasts')
]
class StationStreamerBroadcast implements IdentifiableEntityInterface, Stringable
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    public const string PATH_PREFIX = 'stream';

    #[
        ORM\ManyToOne(inversedBy: 'streamer_broadcasts'),
        ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    protected Station $station;

    #[
        ORM\ManyToOne(inversedBy: 'broadcasts'),
        ORM\JoinColumn(name: 'streamer_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    protected StationStreamer $streamer;

    #[ORM\Column(name: 'timestamp_start', type: 'datetime_immutable', precision: 6)]
    protected DateTimeImmutable $timestampStart;

    #[ORM\Column(name: 'timestamp_end', type: 'datetime_immutable', precision: 6, nullable: true)]
    protected ?DateTimeImmutable $timestampEnd = null;

    #[ORM\Column(name: 'recording_path', length: 255, nullable: true)]
    protected ?string $recordingPath = null;

    public function __construct(StationStreamer $streamer)
    {
        $this->streamer = $streamer;
        $this->station = $streamer->getStation();

        $this->timestampStart = Time::nowUtc();
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getStreamer(): StationStreamer
    {
        return $this->streamer;
    }

    public function getTimestampStart(): DateTimeImmutable
    {
        return $this->timestampStart;
    }

    public function setTimestampStart(mixed $timestampStart): void
    {
        $this->timestampStart = Time::toUtcCarbonImmutable($timestampStart);
    }

    public function getTimestampEnd(): ?DateTimeImmutable
    {
        return $this->timestampEnd;
    }

    public function setTimestampEnd(mixed $timestampEnd): void
    {
        $this->timestampEnd = Time::toNullableUtcCarbonImmutable($timestampEnd);
    }

    public function getRecordingPath(): ?string
    {
        return $this->recordingPath;
    }

    public function setRecordingPath(?string $recordingPath): void
    {
        $this->recordingPath = $recordingPath;
    }

    public function __toString(): string
    {
        $timestampEnd = (null !== $this->timestampEnd)
            ? CarbonImmutable::instance($this->timestampEnd)
            : null;

        return sprintf(
            "%s-%s",
            CarbonImmutable::instance($this->timestampStart)->toAtomString(),
            $timestampEnd?->toAtomString() ?? 'Now'
        );
    }
}
