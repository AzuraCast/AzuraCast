<?php

namespace App\Entity;

use App\Annotations\AuditLog;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use OpenApi\Annotations as OA;

/**
 * @ORM\Table(name="station_schedules")
 * @ORM\Entity
 *
 * @AuditLog\Auditable
 *
 * @OA\Schema(type="object")
 */
class StationSchedule
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @OA\Property(example=1)
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="StationPlaylist", inversedBy="schedules")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="playlist_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     * @var StationPlaylist|null
     */
    protected $playlist;

    /**
     * @ORM\ManyToOne(targetEntity="StationStreamer", inversedBy="schedules")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="streamer_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     * @var StationStreamer|null
     */
    protected $streamer;

    /**
     * @ORM\Column(name="start_time", type="smallint")
     *
     * @OA\Property(example=900)
     *
     * @var int
     */
    protected $start_time = 0;

    /**
     * @ORM\Column(name="end_time", type="smallint")
     *
     * @OA\Property(example=2200)
     *
     * @var int
     */
    protected $end_time = 0;

    /**
     * @ORM\Column(name="start_date", type="string", length=10, nullable=true)
     *
     * @var string|null The optional start date for this scheduled period.
     */
    protected $start_date;

    /**
     * @ORM\Column(name="end_date", type="string", length=10, nullable=true)
     *
     * @var string|null The optional end date for this scheduled period.
     */
    protected $end_date;

    /**
     * @ORM\Column(name="days", type="string", length=50, nullable=true)
     *
     * @OA\Property(example="0,1,2,3")
     *
     * @var string Array of ISO-8601 days (1 for Monday, 7 for Sunday)
     */
    protected $days;

    /**
     * @param StationPlaylist|StationStreamer $relation
     */
    public function __construct($relation)
    {
        if ($relation instanceof StationPlaylist) {
            $this->playlist = $relation;
        } elseif ($relation instanceof StationStreamer) {
            $this->streamer = $relation;
        } else {
            throw new InvalidArgumentException('Schedule must be created with either a playlist or a streamer.');
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlaylist(): ?StationPlaylist
    {
        return $this->playlist;
    }

    public function getStreamer(): ?StationStreamer
    {
        return $this->streamer;
    }

    public function getStartTime(): int
    {
        return (int)$this->start_time;
    }

    public function setStartTime(int $start_time): void
    {
        $this->start_time = $start_time;
    }

    public function getEndTime(): int
    {
        return (int)$this->end_time;
    }

    public function setEndTime(int $end_time): void
    {
        $this->end_time = $end_time;
    }

    /**
     * @return int Get the duration of scheduled play time in seconds (used for remote URLs of indeterminate length).
     */
    public function getDuration(): int
    {
        $start_time = self::getDateTime($this->start_time)
            ->getTimestamp();
        $end_time = self::getDateTime($this->end_time)
            ->getTimestamp();

        if ($start_time > $end_time) {
            return 86400 - ($start_time - $end_time);
        }

        return $end_time - $start_time;
    }

    public function getStartDate(): ?string
    {
        return $this->start_date;
    }

    public function setStartDate(?string $start_date): void
    {
        $this->start_date = $start_date;
    }

    public function getEndDate(): ?string
    {
        return $this->end_date;
    }

    public function setEndDate(?string $end_date): void
    {
        $this->end_date = $end_date;
    }

    /**
     * @return int[]|null
     */
    public function getDays(): ?array
    {
        if (empty($this->days)) {
            return null;
        }

        $days = [];
        foreach (explode(',', $this->days) as $day) {
            $days[] = (int)$day;
        }

        return $days;
    }

    public function setDays($days): void
    {
        $this->days = implode(',', (array)$days);
    }

    public function __toString(): string
    {
        $parts = [];

        $startTimeText = self::displayTimeCode($this->start_time);
        $endTimeText = self::displayTimeCode($this->end_time);
        if ($this->start_time === $this->end_time) {
            $parts[] = $startTimeText;
        } else {
            $parts[] = $startTimeText . ' to ' . $endTimeText;
        }

        if (!empty($this->start_date) || !empty($this->end_date)) {
            if ($this->start_date === $this->end_date) {
                $parts[] = $this->start_date;
            } elseif (empty($this->start_date)) {
                $parts[] = 'Until ' . $this->end_date;
            } elseif (empty($this->end_date)) {
                $parts[] = 'After ' . $this->start_date;
            } else {
                $parts[] = $this->start_date . ' to ' . $this->end_date;
            }
        }

        $days = $this->getDays();
        $daysOfWeek = [
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
            7 => 'Sun',
        ];

        if (null !== $days) {
            $displayDays = [];
            foreach ($days as $day) {
                $displayDays[] = $daysOfWeek[$day];
            }

            $parts[] = implode('/', $displayDays);
        }

        return implode(', ', $parts);
    }

    /**
     * Return a \DateTime object (or null) for a given time code, by default in the UTC time zone.
     *
     * @param string|int $timeCode
     * @param CarbonInterface|null $now
     */
    public static function getDateTime($timeCode, CarbonInterface $now = null): CarbonInterface
    {
        if (null === $now) {
            $now = CarbonImmutable::now(new DateTimeZone('UTC'));
        }

        $timeCode = str_pad($timeCode, 4, '0', STR_PAD_LEFT);
        return $now->setTime((int)substr($timeCode, 0, 2), (int)substr($timeCode, 2));
    }

    public static function displayTimeCode($timeCode): string
    {
        $timeCode = str_pad($timeCode, 4, '0', STR_PAD_LEFT);

        $hours = (int)substr($timeCode, 0, 2);
        $mins = substr($timeCode, 2);

        return $hours . ':' . $mins;
    }
}
