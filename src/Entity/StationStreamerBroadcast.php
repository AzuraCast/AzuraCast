<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Entity\Interfaces\StationMountInterface;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;

/**
 * Each individual broadcast associated with a streamer.
 *
 * @OA\Schema(type="object")
 */
#[
    ORM\Entity,
    ORM\Table(name: 'station_streamer_broadcasts')
]
class StationStreamerBroadcast implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[ORM\ManyToOne(inversedBy: 'streamer_broadcasts')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Station $station;

    #[ORM\ManyToOne(inversedBy: 'broadcasts')]
    #[ORM\JoinColumn(name: 'streamer_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected StationStreamer $streamer;

    #[ORM\Column(name: 'timestamp_start')]
    protected int $timestampStart = 0;

    #[ORM\Column(name: 'timestamp_end')]
    protected int $timestampEnd = 0;

    #[ORM\Column(name: 'recording_path', length: 255, nullable: true)]
    protected ?string $recordingPath = null;

    public function __construct(StationStreamer $streamer)
    {
        $this->streamer = $streamer;
        $this->station = $streamer->getStation();

        $this->timestampStart = time();
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getStreamer(): StationStreamer
    {
        return $this->streamer;
    }

    public function getTimestampStart(): int
    {
        return $this->timestampStart;
    }

    public function getTimestampEnd(): int
    {
        return $this->timestampEnd;
    }

    public function setTimestampEnd(int $timestampEnd): void
    {
        $this->timestampEnd = $timestampEnd;
    }

    public function getRecordingPath(): ?string
    {
        return $this->recordingPath;
    }

    public function generateRecordingPath(string $format = StationMountInterface::FORMAT_MP3): string
    {
        $ext = match (strtolower($format)) {
            StationMountInterface::FORMAT_AAC => 'mp4',
            StationMountInterface::FORMAT_OGG => 'ogg',
            StationMountInterface::FORMAT_OPUS => 'opus',
            default => 'mp3',
        };

        $now = CarbonImmutable::createFromTimestamp(
            $this->timestampStart,
            $this->station->getTimezoneObject()
        );
        $this->recordingPath = $this->streamer->getStreamerUsername() . '/' . $now->format('Ymd-His') . '.' . $ext;

        return $this->recordingPath;
    }

    public function clearRecordingPath(): void
    {
        $this->recordingPath = null;
    }
}
