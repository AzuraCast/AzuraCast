<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="station_playlists")
 * @Entity
 * @HasLifecycleCallbacks
 */
class StationPlaylist extends \App\Doctrine\Entity
{
    public function __construct()
    {
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
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="name", type="string", length=200) */
    protected $name;

    public function getShortName()
    {
        return Station::getStationShortName($this->name);
    }

    /** @Column(name="type", type="string", length=50) */
    protected $type;

    /** @Column(name="is_enabled", type="boolean", nullable=false) */
    protected $is_enabled;

    /** @Column(name="play_per_songs", type="smallint") */
    protected $play_per_songs;

    /** @Column(name="play_per_minutes", type="smallint") */
    protected $play_per_minutes;

    /** @Column(name="schedule_start_time", type="smallint") */
    protected $schedule_start_time;

    public function getScheduleStartTimeText()
    {
        return self::formatTimeCode($this->schedule_start_time);
    }

    /** @Column(name="schedule_end_time", type="smallint") */
    protected $schedule_end_time;

    public function getScheduleEndTimeText()
    {
        return self::formatTimeCode($this->schedule_end_time);
    }

    /** @Column(name="play_once_time", type="smallint") */
    protected $play_once_time;

    public function getPlayOnceTimeText()
    {
        return self::formatTimeCode($this->play_once_time);
    }

    /** @Column(name="weight", type="smallint") */
    protected $weight;

    /** @Column(name="include_in_automation", type="boolean", nullable=false) */
    protected $include_in_automation;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="playlists")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;

    /**
     * @ManyToMany(targetEntity="StationMedia", mappedBy="playlists", fetch="EXTRA_LAZY")
     */
    protected $media;

    /**
     * Given a time code i.e. "2300", return a time i.e. "11:00 PM"
     * @param $time_code
     * @return string
     */
    public static function formatTimeCode($time_code)
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
}