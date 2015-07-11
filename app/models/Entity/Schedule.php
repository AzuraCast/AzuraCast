<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="schedule", indexes={
 *   @index(name="search_idx", columns={"guid", "start_time", "end_time"})
 * })
 * @Entity
 */
class Schedule extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->is_promoted = false;
        $this->is_notified = false;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="station_id", type="integer", nullable=true) */
    protected $station_id;

    /** @Column(name="guid", type="string", length=32, nullable=true) */
    protected $guid;

    /** @Column(name="start_time", type="integer") */
    protected $start_time;

    /** @Column(name="end_time", type="integer") */
    protected $end_time;

    public function getRange()
    {
        return self::getRangeText($this->start_time, $this->end_time, $this->is_all_day);
    }

    /** @Column(name="is_all_day", type="boolean", nullable=true) */
    protected $is_all_day;

    /** @Column(name="title", type="string", length=400, nullable=true) */
    protected $title;

    /** @Column(name="location", type="string", length=400, nullable=true) */
    protected $location;

    /** @Column(name="body", type="text", nullable=true) */
    protected $body;

    public function getImageUrl()
    {
        return self::getRowImageUrl($this);
    }

    /** @Column(name="banner_url", type="string", length=250, nullable=true) */
    protected $banner_url;

    /** @Column(name="web_url", type="string", length=250, nullable=true) */
    protected $web_url;

    /** @Column(name="is_promoted", type="boolean") */
    protected $is_promoted;

    /** @Column(name="is_notified", type="boolean") */
    protected $is_notified;

    /**
     * @ManyToOne(targetEntity="Station")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;

    /**
     * Static Functions
     */

    public static function getUpcomingConventions()
    {
        return Convention::getUpcomingConventions();
    }

    public static function getCurrentEvent($station_id)
    {
        $em = self::getEntityManager();
        $events_now = $em->createQuery('SELECT s FROM '.__CLASS__.' s WHERE (s.station_id = :station_id AND s.start_time <= :current AND s.end_time >= :current) ORDER BY s.start_time ASC')
            ->setParameter('station_id', $station_id)
            ->setParameter('current', time())
            ->useResultCache(true, 90, 'pvl_event_'.$station_id)
            ->getArrayResult();

        if ($events_now)
        {
            $event = $events_now[0];
            $event['range'] = self::getRangeText($event['start_time'], $event['end_time'], $event['is_all_day']);
            return $event;
        }

        return NULL;
    }

    public static function getUpcomingEvent($station_id, $threshold = 1800)
    {
        $start_timestamp = time();
        $end_timestamp = $start_timestamp+$threshold;

        $em = self::getEntityManager();
        $events_raw = $em->createQuery('SELECT s FROM '.__CLASS__.' s WHERE (s.station_id = :station_id AND s.start_time >= :start AND s.start_time < :end) ORDER BY s.start_time ASC')
            ->setParameter('station_id', $station_id)
            ->setParameter('start', $start_timestamp)
            ->setParameter('end', $end_timestamp)
            ->useResultCache(true, 90, 'pvl_event_upcoming_'.$station_id)
            ->getArrayResult();

        if ($events_raw)
        {
            $event = $events_raw[0];

            $event['minutes_until'] = round(($event['start_time'] - time()) / 60);
            $event['range'] = self::getRangeText($event['start_time'], $event['end_time'], $event['is_all_day']);
            return $event;
        }

        return NULL;
    }

    public static function getEventsInRange($station_id, $start_timestamp, $end_timestamp)
    {
        $em = self::getEntityManager();
        $events_now = $em->createQuery('SELECT s FROM '.__CLASS__.' s WHERE (s.station_id = :station_id AND s.start_time <= :end AND s.end_time >= :start) ORDER BY s.start_time ASC')
            ->setParameter('station_id', $station_id)
            ->setParameter('start', $start_timestamp)
            ->setParameter('end', $end_timestamp)
            ->getArrayResult();

        $events = array();
        foreach($events_now as $event)
        {
            $event['range'] = self::getRangeText($event['start_time'], $event['end_time'], $event['is_all_day']);
            $events[] = $event;
        }

        return $events;
    }

    public static function getRangeText($start_time, $end_time, $is_all_day = FALSE)
    {
        $current_date = date('Y-m-d');

        $starts_today = (date('Y-m-d', $start_time) == $current_date);
        $ends_today = (date('Y-m-d', $end_time) == $current_date);
        $dates_match = (date('Y-m-d', $start_time) == date('Y-m-d', $end_time));

        $is_now = ($start_time < time() && $end_time >= time());

        // Special case for "all day today".
        if ($is_all_day && $is_now)
        {
            $range_text = 'All Day Today';
        }
        else
        {
            if ($is_now)
            {
                $range_text = 'Now';
            }
            else
            {
                $range_text = '';

                if ($starts_today)
                    $range_text .= 'Today';
                else
                    $range_text .= date('D F j', $start_time);

                if (!$is_all_day)
                    $range_text .= ' '.date('g:ia', $start_time);
            }

            if ($start_time != $end_time)
            {
                if ($ends_today)
                {
                    $range_text .= ' to '.date('g:ia', $end_time);
                }
                else if ($is_all_day)
                {
                    $range_text .= ' to '.date('g:ia', $end_time);
                }
                else
                {
                    $range_text .= ' to ';
                    if (!$dates_match)
                        $range_text .= date('D F j', $end_time).' ';

                    $range_text .= date('g:ia', $end_time);
                }
            }
        }

        return $range_text;
    }

    public static function getRowImageUrl($row)
    {
        if ($row instanceof Schedule)
            $station = $row->station;
        else
            $station = $row['station'];

        return $station['image_url'];
    }

    public static function formatName($string)
    {
        $string = trim($string);

        // Detect Twitter handles.
        $string = preg_replace_callback('/@([A-Za-z0-9_]{1,15})/', function($matches) {
            $twitter_username = substr($matches[0], 1);
            return '<a href="https://twitter.com/'.$twitter_username.'" target="_blank">@'.$twitter_username.'</a>';
        }, $string);

        return $string;
    }

    public static function api($row_raw)
    {
        if (empty($row_raw))
            return array();

        if ($row_raw instanceof self)
            $row_raw = $row_raw->toArray();

        $row = array(
            'id'            => (int)$row_raw['id'],
            'station_id'    => (int)$row_raw['station_id'],
            'guid'          => $row_raw['guid'],
            'start_time'    => (int)$row_raw['start_time'],
            'end_time'      => (int)$row_raw['end_time'],
            'is_all_day'    => (bool)$row_raw['is_all_day'],
            'title'         => $row_raw['title'],
            'location'      => $row_raw['location'],
            'body'          => $row_raw['body'],
            'banner_url'    => $row_raw['banner_url'],
            'web_url'       => $row_raw['web_url'],
            'range'         => self::getRangeText($row_raw['start_time'], $row_raw['end_time'], $row_raw['is_all_day']),
            'image_url'     => \PVL\Url::upload(self::getRowImageUrl($row_raw)),
        );

        // Add station shortcode.
        if (isset($row['station']))
        {
            $row['station'] = Station::api($row['station']);

            $shortcode = Station::getStationShortName($row['station']['name']);
            $row['station_shortcode'] = $shortcode;
        }

        return $row;
    }
}