<?php

namespace App\Entity;

use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;

/**
 * Each individual broadcast associated with a streamer.
 *
 * @ORM\Table(name="station_streamer_broadcasts")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 *
 * @OA\Schema(type="object")
 */
class StationStreamerBroadcast
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
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="streamer_broadcasts")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\ManyToOne(targetEntity="StationStreamer", inversedBy="broadcasts")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="streamer_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationStreamer
     */
    protected $streamer;

    /**
     * @ORM\Column(name="timestamp_start", type="integer")
     * @var int
     */
    protected $timestampStart = 0;

    /**
     * @ORM\Column(name="timestamp_end", type="integer")
     * @var int
     */
    protected $timestampEnd = 0;

    /**
     * @ORM\Column(name="recording_path", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $recordingPath;

    public function __construct(StationStreamer $streamer)
    {
        $this->streamer = $streamer;
        $this->station = $streamer->getStation();

        $this->timestampStart = time();
    }

    public function getId(): int
    {
        return $this->id;
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
        switch (strtolower($format)) {
            case StationMountInterface::FORMAT_AAC:
                $ext = 'mp4';
                break;

            case StationMountInterface::FORMAT_OGG:
                $ext = 'ogg';
                break;

            case StationMountInterface::FORMAT_OPUS:
                $ext = 'opus';
                break;

            case StationMountInterface::FORMAT_MP3:
            default:
                $ext = 'mp3';
                break;
        }

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
