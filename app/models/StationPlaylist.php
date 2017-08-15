<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Table(name="station_playlists")
 * @Entity
 * @HasLifecycleCallbacks
 */
class StationPlaylist
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="playlists")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @Column(name="name", type="string", length=200)
     * @var string
     */
    protected $name;

    /**
     * @Column(name="type", type="string", length=50)
     * @var string
     */
    protected $type;

    /**
     * @Column(name="is_enabled", type="boolean")
     * @var bool
     */
    protected $is_enabled;

    /**
     * @Column(name="play_per_songs", type="smallint")
     * @var int
     */
    protected $play_per_songs;

    /**
     * @Column(name="play_per_minutes", type="smallint")
     * @var int
     */
    protected $play_per_minutes;

    /**
     * @Column(name="schedule_start_time", type="smallint")
     * @var int
     */
    protected $schedule_start_time;

    /**
     * @Column(name="schedule_end_time", type="smallint")
     * @var int
     */
    protected $schedule_end_time;

    /**
     * @Column(name="play_once_time", type="smallint")
     * @var int
     */
    protected $play_once_time;

    /**
     * @Column(name="weight", type="smallint")
     * @var int
     */
    protected $weight;

    /**
     * @Column(name="include_in_automation", type="boolean")
     * @var bool
     */
    protected $include_in_automation;

    /**
     * @ManyToMany(targetEntity="StationMedia", mappedBy="playlists", fetch="EXTRA_LAZY")
     * @var Collection
     */
    protected $media;

    public function __construct(Station $station)
    {
        $this->station = $station;

        $this->type = 'default';
        $this->is_enabled = 1;

        $this->weight = 3;
        $this->include_in_automation = false;
        $this->play_once_time = 0;
        $this->play_per_minutes = 0;
        $this->play_per_songs = 0;
        $this->schedule_start_time = 0;
        $this->schedule_end_time = 0;

        $this->media = new ArrayCollection;
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
        $this->name = $name;
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
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->is_enabled;
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
        return $this->schedule_start_time;
    }

    /**
     * @return string
     */
    public function getScheduleStartTimeText(): string
    {
        return self::formatTimeCode($this->schedule_start_time);
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
        return $this->schedule_end_time;
    }

    /**
     * @param int $schedule_end_time
     */
    public function setScheduleEndTime(int $schedule_end_time)
    {
        $this->schedule_end_time = $schedule_end_time;
    }

    /**
     * @return int
     */
    public function getPlayOnceTime(): int
    {
        return $this->play_once_time;
    }

    /**
     * @param int $play_once_time
     */
    public function setPlayOnceTime(int $play_once_time)
    {
        $this->play_once_time = $play_once_time;
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
    public function getMedia(): Collection
    {
        return $this->media;
    }

    /**
     * Given a time code i.e. "2300", return a time i.e. "11:00 PM"
     * @param $time_code
     * @return string
     */
    public static function formatTimeCode($time_code): string
    {
        $hours = floor($time_code / 100);
        $mins = $time_code % 100;

        $ampm = ($hours < 12) ? 'AM' : 'PM';

        if ($hours == 0) {
            $hours_text = '12';
        } elseif ($hours > 12) {
            $hours_text = $hours - 12;
        } else {
            $hours_text = $hours;
        }

        return $hours_text . ':' . str_pad($mins, 2, '0', STR_PAD_LEFT) . ' ' . $ampm;
    }

    /**
     * @return string
     */
    public function getScheduleEndTimeText(): string
    {
        return self::formatTimeCode($this->schedule_end_time);
    }

    /**
     * @return string
     */
    public function getPlayOnceTimeText(): string
    {
        return self::formatTimeCode($this->play_once_time);
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
                foreach ($this->media as $media_file) {
                    $media_file_path = $media_path . $media_file->path;
                    $playlist_file[] = $media_file_path;
                }

                shuffle($playlist_file);

                return implode("\n", $playlist_file);
            break;

            case 'pls':
            default:
                $playlist_file = [
                    '[playlist]',
                ];

                $i = 0;
                foreach($this->media as $media_file) {
                    $i++;

                    $media_file_path = $media_path . $media_file->path;
                    $playlist_file[] = 'File'.$i.'='.$media_file_path;
                    $playlist_file[] = 'Title'.$i.'='.$media_file->artist.' - '.$media_file->title;
                    $playlist_file[] = 'Length'.$i.'='.$media_file->length;
                    $playlist_file[] = '';
                }

                $playlist_file[] = 'NumberOfEntries='.$i;
                $playlist_file[] = 'Version=2';

                return implode("\n", $playlist_file);
            break;
        }
    }
}