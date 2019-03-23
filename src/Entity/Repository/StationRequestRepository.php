<?php
namespace App\Entity\Repository;

use App\Entity;
use Azura\Doctrine\Repository;

class StationRequestRepository extends Repository
{
    /**
     * Submit a new request.
     *
     * @param Entity\Station $station
     * @param int $track_id
     * @param bool $is_authenticated
     * @return mixed
     * @throws \Azura\Exception
     */
    public function submit(Entity\Station $station, $track_id, $is_authenticated = false)
    {
        // Forbid web crawlers from using this feature.
        if (\App\Utilities::isCrawler()) {
            throw new \Azura\Exception('Search engine crawlers are not permitted to use this feature.');
        }

        // Verify that the station supports requests.
        if (!$station->getEnableRequests()) {
            throw new \Azura\Exception('This station does not accept requests currently.');
        }

        // Verify that Track ID exists with station.
        $media_repo = $this->_em->getRepository(Entity\StationMedia::class);
        $media_item = $media_repo->findOneBy(['unique_id' => $track_id, 'station_id' => $station->getId()]);

        if (!($media_item instanceof Entity\StationMedia)) {
            throw new \Azura\Exception('The song ID you specified could not be found in the station.');
        }

        if (!$media_item->isRequestable()) {
            throw new \Azura\Exception('The song ID you specified cannot be requested for this station.');
        }

        // Check if the song is already enqueued as a request.
        $this->checkPendingRequest($media_item, $station);

        // Check the most recent song history.
        $this->checkRecentPlay($media_item, $station);

        if (!$is_authenticated) {
            // Check for an existing request from this user.
            $user_ip = $_SERVER['REMOTE_ADDR'];

            // Check for any request (on any station) within the last $threshold_seconds.
            $threshold_mins = $station->getRequestThreshold() ?? 5;
            $threshold_seconds = $threshold_mins * 60;

            $recent_requests = $this->_em->createQuery(/** @lang DQL */'SELECT sr 
                FROM App\Entity\StationRequest sr 
                WHERE sr.ip = :user_ip 
                AND sr.timestamp >= :threshold')
                ->setParameter('user_ip', $user_ip)
                ->setParameter('threshold', time() - $threshold_seconds)
                ->getArrayResult();

            if (count($recent_requests) > 0) {
                $threshold_text = \App\Utilities::timeToText($threshold_seconds);
                throw new \Azura\Exception('You have submitted a request too recently! Please wait '.$threshold_text.' before submitting another one.');
            }
        }

        // Save request locally.
        $record = new Entity\StationRequest($station, $media_item);
        $this->_em->persist($record);
        $this->_em->flush();

        return $record->getId();
    }

    /**
     * Check if the song is already enqueued as a request.
     *
     * @param Entity\StationMedia $media
     * @param Entity\Station $station
     * @return bool
     * @throws \Azura\Exception
     */
    public function checkPendingRequest(Entity\StationMedia $media, Entity\Station $station)
    {
        $pending_request_threshold = time() - (60 * 10);

        try {
            $pending_request = $this->_em->createQuery(/** @lang DQL */'SELECT sr.timestamp 
                FROM App\Entity\StationRequest sr
                WHERE sr.track_id = :track_id 
                AND sr.station_id = :station_id 
                AND (sr.timestamp >= :threshold OR sr.played_at = 0)
                ORDER BY sr.timestamp DESC')
                ->setParameter('track_id', $media->getId())
                ->setParameter('station_id', $station->getId())
                ->setParameter('threshold', $pending_request_threshold)
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return true;
        }

        if ($pending_request > 0) {
            throw new \Azura\Exception('Duplicate request: this song was already requested and will play soon.');
        }

        return true;
    }

    /**
     * Check the most recent song history.
     *
     * @param Entity\StationMedia $media
     * @param Entity\Station $station
     * @return bool
     * @throws \Azura\Exception
     */
    public function checkRecentPlay(Entity\StationMedia $media, Entity\Station $station)
    {
        $last_play_threshold_mins = (int)($station->getRequestThreshold() ?? 15);

        if ($last_play_threshold_mins == 0) {
            return true;
        }

        $last_play_threshold = time() - ($last_play_threshold_mins * 60);

        try {
            $last_play_time = $this->_em->createQuery(/** @lang DQL */'SELECT sh.timestamp_start 
                FROM App\Entity\SongHistory sh 
                WHERE sh.media_id = :media_id 
                AND sh.station_id = :station_id
                AND sh.timestamp_start >= :threshold
                ORDER BY sh.timestamp_start DESC')
                ->setParameter('media_id', $media->getId())
                ->setParameter('station_id', $station->getId())
                ->setParameter('threshold', $last_play_threshold)
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch(\Doctrine\ORM\NoResultException $e) {
            return true;
        }

        if ($last_play_time > 0) {
            $threshold_text = \App\Utilities::timeDifferenceText(time(), $last_play_time);
            throw new \Azura\Exception('This song was already played '.$threshold_text.' ago! Wait a while before requesting it again.');
        }

        return true;
    }
}
