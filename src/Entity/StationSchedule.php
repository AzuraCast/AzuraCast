<?php
namespace App\Entity;

use App\Annotations\AuditLog;
use Cake\Chronos\Chronos;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
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
     * @var string
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
            throw new \InvalidArgumentException('Schedule must be created with either a playlist or a streamer.');
        }
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return StationPlaylist|null
     */
    public function getPlaylist(): ?StationPlaylist
    {
        return $this->playlist;
    }

    /**
     * @return StationStreamer|null
     */
    public function getStreamer(): ?StationStreamer
    {
        return $this->streamer;
    }

    /**
     * @return int
     */
    public function getStartTime(): int
    {
        return (int)$this->start_time;
    }

    /**
     * @param int $start_time
     */
    public function setStartTime(int $start_time): void
    {
        $this->start_time = $start_time;
    }

    /**
     * @return int
     */
    public function getEndTime(): int
    {
        return (int)$this->end_time;
    }

    /**
     * @param int $end_time
     */
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

    /**
     * @return string|null
     */
    public function getStartDate(): ?string
    {
        return $this->start_date;
    }

    /**
     * @param string|null $start_date
     */
    public function setStartDate(?string $start_date): void
    {
        $this->start_date = $start_date;
    }

    /**
     * @return string|null
     */
    public function getEndDate(): ?string
    {
        return $this->end_date;
    }

    /**
     * @param string|null $end_date
     */
    public function setEndDate(?string $end_date): void
    {
        $this->end_date = $end_date;
    }

    /**
     * @return array|null
     */
    public function getDays(): ?array
    {
        return (!empty($this->days)) ? explode(',', $this->days) : null;
    }

    /**
     * @param array $days
     */
    public function setDays($days): void
    {
        $this->days = implode(',', (array)$days);
    }

    /**
     * Parent function for determining whether a playlist of any type can be played by the AutoDJ.
     *
     * @param Chronos $now
     *
     * @return bool
     */
    public function shouldPlayNow(Chronos $now): bool
    {
        if (!$this->shouldPlayOnCurrentDate($now)) {
            return false;
        }

        $startTime = self::getDateTime($this->getStartTime(), $now);
        $endTime = self::getDateTime($this->getEndTime(), $now);

        $comparePeriods = [];

        if ($startTime->equals($endTime)) {
            // Create intervals for "play once" type dates.
            $startTime = $startTime->addMinutes(-15);
            $endTime = $endTime->addMinutes(15);

            $comparePeriods[] = [$startTime, $endTime];
            $comparePeriods[] = [$startTime->addDays(-1), $endTime->addDays(-1)];
            $comparePeriods[] = [$startTime->addDays(1), $endTime->addDays(1)];
        } elseif ($startTime->greaterThan($endTime)) {
            // Create intervals for overnight playlists (one from yesterday to today, one from today to tomorrow).
            $comparePeriods[] = [$startTime->addDays(-1), $endTime];
            $comparePeriods[] = [$startTime, $endTime->addDays(1)];
        } else {
            $comparePeriods[] = [$startTime, $endTime];
        }

        foreach ($comparePeriods as [$start, $end]) {
            /** @var Chronos $start */
            /** @var Chronos $end */
            if ($now->between($start, $end)) {
                $dayToCheck = (int)$start->format('N');

                if ($this->isScheduledToPlayToday($dayToCheck)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function shouldPlayOnCurrentDate(Chronos $now): bool
    {
        if (!empty($this->start_date)) {
            $startDate = Chronos::parse($this->start_date . ' 00:00:00', $now->getTimezone());
            if ($now->lt($startDate)) {
                return false;
            }
        }

        if (!empty($this->end_date)) {
            $endDate = Chronos::parse($this->end_date . ' 23:59:59', $now->getTimezone());
            if ($now->gt($endDate)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Given a day code (1-7) a-la date('N'), return if the playlist can be played on that day.
     *
     * @param int $day_to_check
     *
     * @return bool
     */
    public function isScheduledToPlayToday(int $day_to_check): bool
    {
        $play_once_days = $this->getDays();
        return empty($play_once_days)
            || in_array($day_to_check, $play_once_days);
    }

    /**
     * Return a \DateTime object (or null) for a given time code, by default in the UTC time zone.
     *
     * @param string|int $timeCode
     * @param Chronos|null $now
     *
     * @return Chronos
     */
    public static function getDateTime($timeCode, Chronos $now = null): Chronos
    {
        if ($now === null) {
            $now = Chronos::now(new DateTimeZone('UTC'));
        }

        $timeCode = str_pad($timeCode, 4, '0', STR_PAD_LEFT);
        return $now->setTime(substr($timeCode, 0, 2), substr($timeCode, 2));
    }
}
