<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="station_requests")
 * @Entity
 */
class StationRequest extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->timestamp = time();
        $this->played_at = 0;

        $this->ip = $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="track_id", type="integer") */
    protected $track_id;

    /** @Column(name="timestamp", type="integer") */
    protected $timestamp;

    /** @Column(name="played_at", type="integer") */
    protected $played_at;

    /** @Column(name="ip", type="string", length=40) */
    protected $ip;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="media")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;

    /**
     * @ManyToOne(targetEntity="StationMedia")
     * @JoinColumns({
     *   @JoinColumn(name="track_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $track;

    public static function submit(Station $station, $track_id, $is_authenticated = false)
    {
        $em = self::getEntityManager();

        // Forbid web crawlers from using this feature.
        if (\App\Utilities::isCrawler())
            throw new \App\Exception('Search engine crawlers are not permitted to use this feature.');

        // Verify that the station supports requests.
        if (!$station->enable_requests)
            throw new \App\Exception('This station does not accept requests currently.');

        // Verify that Track ID exists with station.
        $media_item = StationMedia::getRepository()->findOneBy(array('id' => $track_id, 'station_id' => $station->id));
        if (!($media_item instanceof StationMedia))
            throw new \App\Exception('The song ID you specified could not be found in the station.');

        // Check if the song is already enqueued as a request.
        $pending_request = $em->createQuery('SELECT sr FROM '.__CLASS__.' sr WHERE sr.track_id = :track_id AND sr.station_id = :station_id AND sr.played_at = 0')
            ->setParameter('track_id', $track_id)
            ->setParameter('station_id', $station->id)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($pending_request)
            throw new \App\Exception('Duplicate request: this song is already a pending request on this station.');

        // Check the most recent song history.
        try
        {
            $last_play_time = $em->createQuery('SELECT sh.timestamp_start FROM Entity\SongHistory sh WHERE sh.song_id = :song_id AND sh.station_id = :station_id ORDER BY sh.timestamp DESC')
                ->setParameter('song_id', $media_item->song_id)
                ->setParameter('station_id', $station->id)
                ->setMaxResults(1)
                ->getSingleScalarResult();
        }
        catch(\Exception $e)
        {
            $last_play_time = 0;
        }

        if ($last_play_time && $last_play_time > (time() - 60*30))
            throw new \App\Exception('This song has been played too recently on the station.');

        if (!$is_authenticated)
        {
            // Check for an existing request from this user.
            $user_ip = $_SERVER['REMOTE_ADDR'];

            // Check for any request (on any station) within the last $threshold_seconds.
            $threshold_seconds = 30;

            $recent_requests = $em->createQuery('SELECT sr FROM ' . __CLASS__ . ' sr WHERE sr.ip = :user_ip AND sr.timestamp >= :threshold')
                ->setParameter('user_ip', $user_ip)
                ->setParameter('threshold', time() - $threshold_seconds)
                ->getArrayResult();

            if (count($recent_requests) > 0)
                throw new \App\Exception('You have submitted a request too recently! Please wait a while before submitting another one.');
        }

        // Save request locally.
        $record = new self;
        $record->track = $media_item;
        $record->station = $station;
        $record->save();

        return $record->id;
    }

    public static function processPending()
    {
        $stations = Station::fetchAll();

        foreach($stations as $station)
        {
            if (!$station->enable_requests)
                continue;

            $min_minutes = (int)$station->request_delay;
            $threshold_minutes = $min_minutes + mt_rand(0, $min_minutes);

            \App\Debug::log($station->name . ': Random minutes threshold: ' . $threshold_minutes);

            $threshold = time() - ($threshold_minutes * 60);

            // Look up all requests that have at least waited as long as the threshold.
            $em = self::getEntityManager();
            $requests = $em->createQuery('SELECT sr, sm FROM ' . __CLASS__ . ' sr JOIN sr.track sm
                WHERE sr.played_at = 0 AND sr.station_id = :station_id AND sr.timestamp <= :threshold
                ORDER BY sr.id ASC')
                ->setParameter('station_id', $station->id)
                ->setParameter('threshold', $threshold)
                ->execute();

            foreach ($requests as $request)
            {
                \App\Debug::log($station->name . ': Request to play ' . $request->track->artist . ' - ' . $request->track->title);

                // Log the request as played.
                $request->played_at = time();
                $request->save();

                // Send request to the station to play the request.
                $backend = $station->getBackendAdapter();
                $backend->request($request->track->getFullPath());
            }
        }
    }
}