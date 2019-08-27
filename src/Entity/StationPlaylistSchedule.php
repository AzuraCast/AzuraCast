<?php
namespace App\Entity;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Annotations\AuditLog;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="station_playlist_schedules")
 * @ORM\Entity
 *
 * @AuditLog\Auditable
 *
 * @OA\Schema(type="object")
 */
class StationPlaylistSchedule
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
     * @ORM\Column(name="playlist_id", type="integer")
     * @var int
     */
    protected $playlist_id;

    /**
     * @ORM\ManyToOne(targetEntity="StationPlaylist", inversedBy="schedules")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="playlist_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationPlaylist
     */
    protected $playlist;

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
     * @ORM\Column(name="days", type="string", length=50, nullable=true)
     *
     * @OA\Property(example="0,1,2,3")
     *
     * @var string
     */
    protected $days;

    /**
     * @param StationPlaylist $playlist
     */
    public function __construct(StationPlaylist $playlist)
    {
        $this->playlist = $playlist;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return StationPlaylist
     */
    public function getPlaylist(): StationPlaylist
    {
        return $this->playlist;
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
            /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
            return 86400 - ($start_time - $end_time);
        }

        return $end_time - $start_time;
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
     * @return bool
     */
    public function shouldPlayNow(Chronos $now): bool
    {
        $day_to_check = (int)$now->format('N');
        $current_timecode = (int)$now->format('Hi');

        $schedule_start_time = $this->getStartTime();
        $schedule_end_time = $this->getEndTime();

        // Special handling for playlists ending at midnight (hour code "000").
        if (0 === $schedule_end_time) {
            $schedule_end_time = 2400;
        }

        // Handle overnight playlists that stretch into the next day.
        if ($schedule_end_time < $schedule_start_time) {
            if ($current_timecode <= $schedule_end_time) {
                // Check the previous day, since it's before the end time.
                $day_to_check = (1 === $day_to_check) ? 7 : $day_to_check - 1;
            } else if ($current_timecode < $schedule_start_time) {
                // The playlist shouldn't be playing before the start time on the current date.
                return false;
            }
            // Non-overnight playlist check
        } else if ($current_timecode < $schedule_start_time || $current_timecode > $schedule_end_time) {
            return false;
        }

        // Check that the current day is one of the scheduled play days.
        if (!$this->isScheduledToPlayToday($day_to_check)) {
            return false;
        }

        return true;
    }

    /**
     * Given a day code (1-7) a-la date('N'), return if the playlist can be played on that day.
     *
     * @param int $day_to_check
     * @return bool
     */
    protected function isScheduledToPlayToday(int $day_to_check): bool
    {
        $play_once_days = $this->getDays();
        return empty($play_once_days)
            || in_array($day_to_check, $play_once_days);
    }

    /**
     * Return a \DateTime object (or null) for a given time code, by default in the UTC time zone.
     *
     * @param string|int $time_code
     * @param Chronos|null $now
     * @return Chronos
     */
    public static function getDateTime($time_code, Chronos $now = null): Chronos
    {
        if ($now === null) {
            $now = Chronos::now(new \DateTimeZone('UTC'));
        }

        $time_code = str_pad($time_code, 4, '0', STR_PAD_LEFT);
        return $now->setTime(substr($time_code, 0, 2), substr($time_code, 2));
    }
}
