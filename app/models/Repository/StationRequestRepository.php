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
        if (\App\Utilities::is_crawler()) {
            throw new \App\Exception('Search engine crawlers are not permitted to use this feature.');
        }

        // Verify that the station supports requests.
        if (!$station->enable_requests) {
            throw new \App\Exception('This station does not accept requests currently.');
        }

        // Verify that Track ID exists with station.
        $media_repo = $this->_em->getRepository(Entity\StationMedia::class);
        $media_item = $media_repo->findOneBy(['id' => $track_id, 'station_id' => $station->id]);

        if (!($media_item instanceof Entity\StationMedia)) {
            throw new \App\Exception('The song ID you specified could not be found in the station.');
        }

        // Check if the song is already enqueued as a request.
        $pending_request_threshold = time() - (60 * 30);

        try {
            $pending_request = $this->_em->createQuery('SELECT sr.timestamp 
                FROM ' . $this->_entityName . ' sr
                WHERE sr.track_id = :track_id 
                AND sr.station_id = :station_id 
                AND (sr.timestamp >= :threshold OR sr.played_at = 0)
                ORDER BY sr.timestamp DESC')
                ->setParameter('track_id', $track_id)
                ->setParameter('station_id', $station->id)
                ->setParameter('threshold', $pending_request_threshold)
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            $pending_request = 0;
        }

        if ($pending_request > 0) {
            throw new \App\Exception('Duplicate request: this song was already requested and will play soon.');
        }

        // Check the most recent song history.
        $last_play_threshold = time() - (60 * 60);

        try {
            $last_play_time = $this->_em->createQuery('SELECT sh.timestamp_start 
                FROM Entity\SongHistory sh 
                WHERE sh.song_id = :song_id 
                AND sh.station_id = :station_id
                AND sh.timestamp_start >= :threshold
                ORDER BY sh.timestamp_start DESC')
                ->setParameter('song_id', $media_item->song_id)
                ->setParameter('station_id', $station->id)
                ->setParameter('threshold', $last_play_threshold)
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            $last_play_time = 0;
        }

        if ($last_play_time > 0) {
            $threshold_text = \App\Utilities::timeDifferenceText(time(), $last_play_time);
            throw new \App\Exception('This song was already played '.$threshold_text.' ago! Wait a while before requesting it again.');
        }

        if (!$is_authenticated) {
            // Check for an existing request from this user.
            $user_ip = $_SERVER['REMOTE_ADDR'];

            // Check for any request (on any station) within the last $threshold_seconds.
            $threshold_seconds = 300;

            $recent_requests = $this->_em->createQuery('SELECT sr FROM ' . $this->_entityName . ' sr WHERE sr.ip = :user_ip AND sr.timestamp >= :threshold')
                ->setParameter('user_ip', $user_ip)
                ->setParameter('threshold', time() - $threshold_seconds)
                ->getArrayResult();

            if (count($recent_requests) > 0) {
                $threshold_text = \App\Utilities::timeToText($threshold_seconds);
                throw new \App\Exception('You have submitted a request too recently! Please wait '.$threshold_text.' before submitting another one.');
            }
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