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
final class StationStreamerBroadcast implements IdentifiableEntityInterface, Stringable
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    public const string PATH_PREFIX = 'stream';

    #[
        ORM\ManyToOne(inversedBy: 'streamer_broadcasts'),
        ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    public readonly Station $station;

    #[
        ORM\ManyToOne(inversedBy: 'broadcasts'),
        ORM\JoinColumn(name: 'streamer_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    public readonly StationStreamer $streamer;

    #[ORM\Column(name: 'timestamp_start', type: 'datetime_immutable', precision: 6)]
    public readonly DateTimeImmutable $timestampStart;

    #[ORM\Column(name: 'timestamp_end', type: 'datetime_immutable', precision: 6, nullable: true)]
    public ?DateTimeImmutable $timestampEnd = null {
        set (DateTimeImmutable|string|int|null $value) => Time::toNullableUtcCarbonImmutable($value);
    }

    #[ORM\Column(name: 'recording_path', length: 255, nullable: true)]
    public ?string $recordingPath = null;

    public function __construct(
        StationStreamer $streamer,
        DateTimeImmutable|string|int|null $timestampStart = null
    ) {
        $this->streamer = $streamer;
        $this->station = $streamer->station;

        $this->timestampStart = Time::toNullableUtcCarbonImmutable($timestampStart) ?? Time::nowUtc();
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
