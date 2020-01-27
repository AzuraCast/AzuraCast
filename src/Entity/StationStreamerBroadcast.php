<?php
namespace App\Entity;

use App\Annotations\AuditLog;
use App\Validator\Constraints as AppAssert;
use Cake\Chronos\Chronos;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

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

        $this->timestampStart = time();
    }

    /**
     * @return StationStreamer
     */
    public function getStreamer(): StationStreamer
    {
        return $this->streamer;
    }

    /**
     * @param StationStreamer $streamer
     */
    public function setStreamer(StationStreamer $streamer): void
    {
        $this->streamer = $streamer;
    }

    /**
     * @return int
     */
    public function getTimestampStart(): int
    {
        return $this->timestampStart;
    }

    /**
     * @return int
     */
    public function getTimestampEnd(): int
    {
        return $this->timestampEnd;
    }

    /**
     * @param int $timestampEnd
     */
    public function setTimestampEnd(int $timestampEnd): void
    {
        $this->timestampEnd = $timestampEnd;
    }

    /**
     * @return string|null
     */
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

        $now = Chronos::createFromTimestampUTC($this->timestampStart);

        return $this->streamer->getStreamerUsername().'/'.$now->format('Ymd-gis').'.'.$ext;
    }
}
