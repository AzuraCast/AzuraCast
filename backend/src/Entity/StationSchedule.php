<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Utilities\Time;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'station_schedules'),
    Attributes\Auditable
]
final class StationSchedule implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;

    #[
        ORM\ManyToOne(inversedBy: 'schedule_items'),
        ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')
    ]
    public ?StationPlaylist $playlist = null {
        set {
            if ($value !== null) {
                $this->streamer = null;
            }

            $this->playlist = $value;
        }
    }

    #[
        ORM\ManyToOne(inversedBy: 'schedule_items'),
        ORM\JoinColumn(name: 'streamer_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')
    ]
    public ?StationStreamer $streamer = null {
        set {
            if ($value !== null) {
                $this->playlist = null;
            }

            $this->streamer = $value;
        }
    }

    #[
        OA\Property(example: 900),
        ORM\Column(type: 'smallint')
    ]
    public int $start_time = 0;

    #[
        OA\Property(example: 2200),
        ORM\Column(type: 'smallint')
    ]
    public int $end_time = 0;

    #[ORM\Column(length: 10, nullable: true)]
    public ?string $start_date = null;

    #[ORM\Column(length: 10, nullable: true)]
    public ?string $end_date = null;

    #[ORM\Column(name: 'days', length: 50, nullable: true)]
    private ?string $days_raw = null;

    /** @var int[] */
    #[
        OA\Property(
            description: "Array of ISO-8601 days (1 for Monday, 7 for Sunday)",
            example: "0,1,2,3"
        )
    ]
    public array $days {
        get {
            if (empty($this->days_raw)) {
                return [];
            }

            return array_map(
                fn($day) => (int)$day,
                explode(',', $this->days_raw)
            );
        }
        set {
            $this->days_raw = implode(',', $value);
        }
    }

    #[
        OA\Property(example: false),
        ORM\Column
    ]
    public bool $loop_once = false;

    public function __construct(StationPlaylist|StationStreamer $relation)
    {
        if ($relation instanceof StationPlaylist) {
            $this->playlist = $relation;
        } else {
            $this->streamer = $relation;
        }
    }

    /**
     * @return int Get the duration of scheduled play time in seconds (used for remote URLs of indeterminate length).
     */
    public function getDuration(DateTimeZone $tz): int
    {
        $now = CarbonImmutable::now($tz);

        $startTime = self::getDateTime($this->start_time, $tz, $now)
            ->getTimestamp();

        $endTime = self::getDateTime($this->end_time, $tz, $now)
            ->getTimestamp();

        if ($startTime > $endTime) {
            /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
            return 86400 - ($startTime - $endTime);
        }

        return $endTime - $startTime;
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

        $days = $this->days;
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
     * @param DateTimeZone $tz The station's time zone.
     * @param DateTimeImmutable|null $now The current date/time.
     * @return CarbonImmutable The current date/time, with the time set to the time code specified.
     */
    public static function getDateTime(
        int|string $timeCode,
        DateTimeZone $tz,
        ?DateTimeImmutable $now = null,
    ): CarbonImmutable {
        $now = CarbonImmutable::instance(Time::nowInTimezone($tz, $now));

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
