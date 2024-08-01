<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'station_schedules'),
    Attributes\Auditable
]
class StationSchedule implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;

    #[
        ORM\ManyToOne(inversedBy: 'schedule_items'),
        ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')
    ]
    protected ?StationPlaylist $playlist = null;

    #[
        ORM\ManyToOne(inversedBy: 'schedule_items'),
        ORM\JoinColumn(name: 'streamer_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')
    ]
    protected ?StationStreamer $streamer = null;

    #[
        OA\Property(example: 900),
        ORM\Column(type: 'smallint')
    ]
    protected int $start_time = 0;

    #[
        OA\Property(example: 2200),
        ORM\Column(type: 'smallint')
    ]
    protected int $end_time = 0;

    #[ORM\Column(length: 10, nullable: true)]
    protected ?string $start_date = null;

    #[ORM\Column(length: 10, nullable: true)]
    protected ?string $end_date = null;

    #[
        OA\Property(
            description: "Array of ISO-8601 days (1 for Monday, 7 for Sunday)",
            example: "0,1,2,3"
        ),
        ORM\Column(length: 50, nullable: true)
    ]
    protected ?string $days = null;

    #[
        OA\Property(example: false),
        ORM\Column
    ]
    protected bool $loop_once = false;

    public function __construct(StationPlaylist|StationStreamer $relation)
    {
        if ($relation instanceof StationPlaylist) {
            $this->playlist = $relation;
        } else {
            $this->streamer = $relation;
        }
    }

    public function getPlaylist(): ?StationPlaylist
    {
        return $this->playlist;
    }

    public function setPlaylist(StationPlaylist $playlist): void
    {
        $this->playlist = $playlist;
        $this->streamer = null;
    }

    public function getStreamer(): ?StationStreamer
    {
        return $this->streamer;
    }

    public function setStreamer(StationStreamer $streamer): void
    {
        $this->streamer = $streamer;
        $this->playlist = null;
    }

    public function getStartTime(): int
    {
        return $this->start_time;
    }

    public function setStartTime(int $startTime): void
    {
        $this->start_time = $startTime;
    }

    public function getEndTime(): int
    {
        return $this->end_time;
    }

    public function setEndTime(int $endTime): void
    {
        $this->end_time = $endTime;
    }

    /**
     * @return int Get the duration of scheduled play time in seconds (used for remote URLs of indeterminate length).
     */
    public function getDuration(): int
    {
        $now = CarbonImmutable::now(new DateTimeZone('UTC'));

        $startTime = self::getDateTime($this->start_time, $now)
            ->getTimestamp();

        $endTime = self::getDateTime($this->end_time, $now)
            ->getTimestamp();

        if ($startTime > $endTime) {
            /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
            return 86400 - ($startTime - $endTime);
        }

        return $endTime - $startTime;
    }

    public function getStartDate(): ?string
    {
        return $this->start_date;
    }

    public function setStartDate(?string $startDate): void
    {
        $this->start_date = $startDate;
    }

    public function getEndDate(): ?string
    {
        return $this->end_date;
    }

    public function setEndDate(?string $endDate): void
    {
        $this->end_date = $endDate;
    }

    /**
     * @return int[]
     */
    public function getDays(): array
    {
        if (empty($this->days)) {
            return [];
        }

        $days = [];
        foreach (explode(',', $this->days) as $day) {
            $days[] = (int)$day;
        }

        return $days;
    }

    public function setDays(array $days): void
    {
        $this->days = implode(',', $days);
    }

    public function getLoopOnce(): bool
    {
        return $this->loop_once;
    }

    public function setLoopOnce(bool $loopOnce): void
    {
        $this->loop_once = $loopOnce;
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

        if ([] !== $days) {
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
     * @param int|string $timeCode
     * @param CarbonInterface $now The current date/time. Note that this time MUST be using the station's timezone
     *   for this function to be accurate.
     * @return CarbonInterface The current date/time, with the time set to the time code specified.
     */
    public static function getDateTime(int|string $timeCode, CarbonInterface $now): CarbonInterface
    {
        $timeCode = str_pad((string)$timeCode, 4, '0', STR_PAD_LEFT);

        return $now->setTime(
            (int)substr($timeCode, 0, 2),
            (int)substr($timeCode, 2)
        );
    }

    public static function displayTimeCode(string|int $timeCode): string
    {
        $timeCode = str_pad((string)$timeCode, 4, '0', STR_PAD_LEFT);

        $hours = (int)substr($timeCode, 0, 2);
        $mins = substr($timeCode, 2);

        return $hours . ':' . $mins;
    }
}
