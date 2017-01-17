<?php
namespace Entity\Repository;

use Entity;

class StationRequestRepository extends \App\Doctrine\Repository
{
    /**
     * Submit a new request.
     *
     * @param Entity\Station $station
     * @param $track_id
     * @param bool $is_authenticated
     * @return mixed
     * @throws \App\Exception
     */
    public function submit(Entity\Station $station, $track_id, $is_authenticated = false)
    {
        // Forbid web crawlers from using this feature.
        if (\App\Utilities::is_crawler())
            throw new \App\Exception('Search engine crawlers are not permitted to use this feature.');

        // Verify that the station supports requests.
        if (!$station->enable_requests)
            throw new \App\Exception('This station does not accept requests currently.');

        // Verify that Track ID exists with station.
        $media_repo = $this->_em->getRepository(Entity\StationMedia::class);
        $media_item = $media_repo->findOneBy(array('id' => $track_id, 'station_id' => $station->id));

        if (!($media_item instanceof Entity\StationMedia))
            throw new \App\Exception('The song ID you specified could not be found in the station.');

        // Check if the song is already enqueued as a request.
        $pending_request = $this->_em->createQuery('SELECT sr FROM '.$this->_entityName.' sr WHERE sr.track_id = :track_id AND sr.station_id = :station_id AND sr.played_at = 0')
            ->setParameter('track_id', $track_id)
            ->setParameter('station_id', $station->id)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($pending_request)
            throw new \App\Exception('Duplicate request: this song is already a pending request on this station.');

        // Check the most recent song history.
        try
        {
            $last_play_time = $this->_em->createQuery('SELECT sh.timestamp_start FROM Entity\SongHistory sh WHERE sh.song_id = :song_id AND sh.station_id = :station_id ORDER BY sh.timestamp_start DESC')
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

            $recent_requests = $this->_em->createQuery('SELECT sr FROM ' . $this->_entityName . ' sr WHERE sr.ip = :user_ip AND sr.timestamp >= :threshold')
                ->setParameter('user_ip', $user_ip)
                ->setParameter('threshold', time() - $threshold_seconds)
                ->getArrayResult();

            if (count($recent_requests) > 0)
                throw new \App\Exception('You have submitted a request too recently! Please wait a while before submitting another one.');
        }

        // Save request locally.
        $record = new Entity\StationRequest;
        $record->track = $media_item;
        $record->station = $station;

        $this->_em->persist($record);
        $this->_em->flush();

        return $record->id;
    }
}