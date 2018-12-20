<?php
namespace App\Entity;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use DateTime;

/**
 * @ORM\Table(name="station_playlists")
 * @ORM\Entity
 * @HasLifecycleCallbacks
 */
class StationPlaylist
{
    use Traits\TruncateStrings;

    public const TYPE_DEFAULT = 'default';
    public const TYPE_SCHEDULED = 'scheduled';
    public const TYPE_ONCE_PER_X_SONGS = 'once_per_x_songs';
    public const TYPE_ONCE_PER_X_MINUTES = 'once_per_x_minutes';
    public const TYPE_ONCE_PER_DAY = 'once_per_day';
    public const TYPE_ADVANCED = 'custom';

    public const SOURCE_SONGS = 'songs';
    public const SOURCE_REMOTE_URL ='remote_url';

    public const REMOTE_TYPE_STREAM = 'stream';
    public const REMOTE_TYPE_PLAYLIST = 'playlist';

    public const ORDER_RANDOM = 'random';
    public const ORDER_SHUFFLE = 'shuffle';
    public const ORDER_SEQUENTIAL = 'sequential';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="playlists")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="name", type="string", length=200)
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(name="type", type="string", length=50)
     * @var string
     */
    protected $type = self::TYPE_DEFAULT;

    /**
     * @ORM\Column(name="source", type="string", length=50)
     * @var string
     */
    protected $source = self::SOURCE_SONGS;

    /**
     * @ORM\Column(name="playback_order", type="string", length=50)
     * @var string
     */
    protected $order = self::ORDER_SHUFFLE;

    /**
     * @ORM\Column(name="remote_url", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $remote_url;

    /**
     * @ORM\Column(name="remote_type", type="string", length=25, nullable=true)
     * @var string|null
     */
    protected $remote_type = self::REMOTE_TYPE_STREAM;

    /**
     * @ORM\Column(name="is_enabled", type="boolean")
     * @var bool
     */
    protected $is_enabled = true;

    /**
     * @ORM\Column(name="play_per_songs", type="smallint")
     * @var int
     */
    protected $play_per_songs = 0;

    /**
     * @ORM\Column(name="play_per_minutes", type="smallint")
     * @var int
     */
    protected $play_per_minutes = 0;

    /**
     * @ORM\Column(name="schedule_start_time", type="smallint")
     * @var int
     */
    protected $schedule_start_time = 0;

    /**
     * @ORM\Column(name="schedule_end_time", type="smallint")
     * @var int
     */
    protected $schedule_end_time = 0;

    /**
     * @ORM\Column(name="schedule_days", type="string", length=50, nullable=true)
     * @var string
     */
    protected $schedule_days;

    /**
     * @ORM\Column(name="play_once_time", type="smallint")
     * @var int
     */
    protected $play_once_time = 0;

    /**
     * @ORM\Column(name="play_once_days", type="string", length=50, nullable=true)
     * @var string
     */
    protected $play_once_days;

    /**
     * @ORM\Column(name="weight", type="smallint")
     * @var int
     */
    protected $weight = 3;

    /**
     * @ORM\Column(name="include_in_requests", type="boolean")
     * @var bool
     */
    protected $include_in_requests = true;

    /**
     * @ORM\Column(name="include_in_automation", type="boolean")
     * @var bool
     */
    protected $include_in_automation = false;

    /**
     * @ORM\OneToMany(targetEntity="StationPlaylistMedia", mappedBy="playlist", fetch="EXTRA_LAZY")
     * @OrderBy({"weight" = "ASC"})
     * @var Collection
     */
    protected $media_items;

    public function __construct(Station $station)
    {
        $this->station = $station;

        $this->media_items = new ArrayCollection;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getShortName(): string
    {
        return Station::getStationShortName($this->name);
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $this->_truncateString($name, 200);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * @param string $order
     */
    public function setOrder(string $order): void
    {
        $this->order = $order;
    }

    /**
     * @return null|string
     */
    public function getRemoteUrl(): ?string
    {
        return $this->remote_url;
    }

    /**
     * @param null|string $remote_url
     */
    public function setRemoteUrl(?string $remote_url): void
    {
        $this->remote_url = $remote_url;
    }

    /**
     * @return string
     */
    public function getRemoteType(): ?string
    {
        return $this->remote_type;
    }

    /**
     * @param null|string $remote_type
     */
    public function setRemoteType(?string $remote_type): void
    {
        $this->remote_type = $remote_type;
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * Indicates whether a playlist is enabled and has content which can be scheduled by an AutoDJ scheduler.
     *
     * @return bool
     */
    public function isPlayable(): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        if (self::SOURCE_SONGS === $this->source) {
            return ($this->media_items->count() > 0);
        }

        return true;
    }

    /**
     * @param bool $is_enabled
     */
    public function setIsEnabled(bool $is_enabled)
    {
        $this->is_enabled = $is_enabled;
    }

    /**
     * @return int
     */
    public function getPlayPerSongs(): int
    {
        return $this->play_per_songs;
    }

    /**
     * @param int $play_per_songs
     */
    public function setPlayPerSongs(int $play_per_songs)
    {
        $this->play_per_songs = $play_per_songs;
    }

    /**
     * @return int
     */
    public function getPlayPerMinutes(): int
    {
        return $this->play_per_minutes;
    }

    /**
     * @param int $play_per_minutes
     */
    public function setPlayPerMinutes(int $play_per_minutes)
    {
        $this->play_per_minutes = $play_per_minutes;
    }

    /**
     * @return int
     */
    public function getScheduleStartTime(): int
    {
        return (int)$this->schedule_start_time;
    }

    /**
     * @return string
     */
    public function getScheduleStartTimeText(): string
    {
        return self::formatTimeCodeForInput($this->schedule_start_time);
    }

    /**
     * @param int $schedule_start_time
     */
    public function setScheduleStartTime(int $schedule_start_time)
    {
        $this->schedule_start_time = $schedule_start_time;
    }

    /**
     * @return int
     */
    public function getScheduleEndTime(): int
    {
        return (int)$this->schedule_end_time;
    }

    /**
     * @return string
     */
    public function getScheduleEndTimeText(): string
    {
        return self::formatTimeCodeForInput($this->schedule_end_time);
    }

    /**
     * @param int $schedule_end_time
     */
    public function setScheduleEndTime(int $schedule_end_time)
    {
        $this->schedule_end_time = $schedule_end_time;
    }

    /**
     * @return int Get the duration of scheduled play time in seconds (used for remote URLs of indeterminate length).
     */
    public function getScheduleDuration(): int
    {
        if (self::TYPE_SCHEDULED !== $this->type) {
            return 0;
        }

        $start_time = self::getTimestamp($this->schedule_start_time);
        $end_time = self::getTimestamp($this->schedule_end_time);

        if ($start_time > $end_time) {
            return 86400 - ($start_time - $end_time);
        }

        return $end_time - $start_time;
    }

    /**
     * @return array|null
     */
    public function getScheduleDays(): ?array
    {
        return (!empty($this->schedule_days)) ? explode(',', $this->schedule_days) : null;
    }

    /**
     * @param array $schedule_days
     */
    public function setScheduleDays($schedule_days)
    {
        $this->schedule_days = implode(',', (array)$schedule_days);
    }

    /**
     * Returns whether the playlist is scheduled to play according to schedule rules.
     *
     * @param Chronos|null $now
     * @return bool
     */
    public function canPlayScheduled(Chronos $now = null): bool
    {
        if ($now === null) {
            $now = Chronos::now(new \DateTimeZone('UTC'));
        }

        $day_to_check = (int)$now->format('N');
        $current_timecode = self::getCurrentTimeCode($now);

        $schedule_start_time = $this->getScheduleStartTime();
        $schedule_end_time = $this->getScheduleEndTime();

        // Handle all-day playlists.
        if ($schedule_start_time === $schedule_end_time) {
            return $this->canPlayScheduledOnDay($day_to_check);
        }

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

            return $this->canPlayScheduledOnDay($day_to_check);
        }

        // Non-overnight playlist check
        return $this->canPlayScheduledOnDay($day_to_check) &&
            ($current_timecode >= $schedule_start_time && $current_timecode <= $schedule_end_time);
    }

    /**
     * Given a day code (1-7) a-la date('N'), return if the playlist can be played on that day.
     *
     * @param $day_to_check
     * @return bool
     */
    public function canPlayScheduledOnDay($day_to_check): bool
    {
        $play_once_days = $this->getScheduleDays();
        return empty($play_once_days)
            || in_array($day_to_check, $play_once_days);
    }

    /**
     * @return int
     */
    public function getPlayOnceTime(): int
    {
        return $this->play_once_time;
    }

    /**
     * @return string
     */
    public function getPlayOnceTimeText(): string
    {
        return self::formatTimeCodeForInput($this->play_once_time);
    }

    /**
     * @param int $play_once_time
     */
    public function setPlayOnceTime(int $play_once_time)
    {
        $this->play_once_time = $play_once_time;
    }

    /**
     * @return array
     */
    public function getPlayOnceDays(): array
    {
        return explode(',', $this->play_once_days);
    }

    /**
     * @param array $play_once_days
     */
    public function setPlayOnceDays($play_once_days)
    {
        $this->play_once_days = implode(',', (array)$play_once_days);
    }

    /**
     * Returns whether the playlist is scheduled to play once.
     * @return bool
     */
    public function canPlayOnce(): bool
    {
        $play_once_days = $this->getPlayOnceDays();

        if (!empty($play_once_days) && !in_array(gmdate('N'), $play_once_days)) {
            return false;
        }

        $current_timecode = self::getCurrentTimeCode();

        $playlist_play_time = $this->getPlayOnceTime();
        $playlist_diff = $current_timecode - $playlist_play_time;

        return ($playlist_diff > 0 && $playlist_diff <= 15);
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * @param int $weight
     */
    public function setWeight(int $weight)
    {
        $this->weight = $weight;
    }

    /**
     * @return bool
     */
    public function getIncludeInRequests(): bool
    {
        return $this->include_in_requests;
    }

    /**
     * Indicates whether this playlist can be used as a valid source of requestable media.
     *
     * @return bool
     */
    public function isRequestable(): bool
    {
        return ($this->is_enabled && $this->include_in_requests);
    }

    /**
     * @param bool $include_in_requests
     */
    public function setIncludeInRequests(bool $include_in_requests): void
    {
        $this->include_in_requests = $include_in_requests;
    }

    /**
     * @return bool
     */
    public function getIncludeInAutomation(): bool
    {
        return $this->include_in_automation;
    }

    /**
     * @param bool $include_in_automation
     */
    public function setIncludeInAutomation(bool $include_in_automation)
    {
        $this->include_in_automation = $include_in_automation;
    }

    /**
     * @return Collection
     */
    public function getMediaItems(): Collection
    {
        return $this->media_items;
    }

    /**
     * Export the playlist into a reusable format.
     *
     * @param string $file_format
     * @param bool $absolute_paths
     * @return string
     */
    public function export($file_format = 'pls', $absolute_paths = false)
    {
        $media_path = ($absolute_paths) ? $this->station->getRadioMediaDir().'/' : '';

        switch($file_format)
        {
            case 'm3u':
                $playlist_file = [];
                foreach ($this->media_items as $media_item) {
                    $media_file = $media_item->getMedia();
                    $media_file_path = $media_path . $media_file->getPath();
                    $playlist_file[] = $media_file_path;
                }

                return implode("\n", $playlist_file);
                break;

            case 'pls':
            default:
                $playlist_file = [
                    '[playlist]',
                ];

                $i = 0;
                foreach($this->media_items as $media_item) {
                    $i++;

                    $media_file = $media_item->getMedia();
                    $media_file_path = $media_path . $media_file->getPath();
                    $playlist_file[] = 'File'.$i.'='.$media_file_path;
                    $playlist_file[] = 'Title'.$i.'='.$media_file->getArtist().' - '.$media_file->getTitle();
                    $playlist_file[] = 'Length'.$i.'='.$media_file->getLength();
                    $playlist_file[] = '';
                }

                $playlist_file[] = 'NumberOfEntries='.$i;
                $playlist_file[] = 'Version=2';

                return implode("\n", $playlist_file);
                break;
        }
    }

    /**
     * Given a time code i.e. "2300", return a UNIX timestamp that can be used to format the time for display.
     *
     * @param $time_code
     * @return int
     */
    public static function getTimestamp($time_code): int
    {
        return self::getDateTime($time_code)
            ->getTimestamp();
    }

    /**
     * Given a time code i.e. "2300", return a time suitable for HTML5 inputs, i.e. "23:00".
     *
     * @param $time_code
     * @return string
     */
    public static function formatTimeCodeForInput($time_code): string
    {
        $now = Chronos::now(new \DateTimeZone(date_default_timezone_get()));
        return self::getDateTime($time_code, $now)
            ->format('H:i');
    }

    /**
     * Return a \DateTime object (or null) for a given time code, by default in the UTC time zone.
     *
     * @param $time_code
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

    /**
     * Return the current UTC time in "time code" style.
     *
     * @param Chronos|null $now
     * @return int
     */
    public static function getCurrentTimeCode(Chronos $now = null): int
    {
        if ($now === null) {
            $now = Chronos::now(new \DateTimeZone('UTC'));
        }

        return $now->format('Hi');
    }
}
