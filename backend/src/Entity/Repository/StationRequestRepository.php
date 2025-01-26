<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Api\StationPlaylistQueue;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationRequest;
use App\Radio\AutoDJ;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Exception as PhpException;

/**
 * @extends AbstractStationBasedRepository<StationRequest>
 */
final class StationRequestRepository extends AbstractStationBasedRepository
{
    protected string $entityClass = StationRequest::class;

    public function __construct(
        private readonly AutoDJ\DuplicatePrevention $duplicatePrevention,
    ) {
    }

    public function getPendingRequest(int|string $id, Station $station): ?StationRequest
    {
        return $this->repository->findOneBy(
            [
                'id' => $id,
                'station' => $station,
                'played_at' => 0,
            ]
        );
    }

    public function clearPendingRequests(Station $station): void
    {
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationRequest sr
                WHERE sr.station = :station
                AND sr.played_at = 0
            DQL
        )->setParameter('station', $station)
            ->execute();
    }

    /**
     * Check if the song is already enqueued as a request.
     */
    public function isTrackPending(StationMedia $media, Station $station): bool
    {
        $pendingRequestThreshold = time() - (60 * 10);

        try {
            $pendingRequest = $this->em->createQuery(
                <<<'DQL'
                    SELECT sr.timestamp
                    FROM App\Entity\StationRequest sr
                    WHERE sr.track_id = :track_id
                    AND sr.station_id = :station_id
                    AND (sr.timestamp >= :threshold OR sr.played_at = 0)
                    ORDER BY sr.timestamp DESC
                DQL
            )->setParameter('track_id', $media->getId())
                ->setParameter('station_id', $station->getId())
                ->setParameter('threshold', $pendingRequestThreshold)
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (PhpException) {
            return false;
        }

        return ($pendingRequest > 0);
    }

    public function getNextPlayableRequest(
        Station $station,
        ?CarbonInterface $now = null
    ): ?StationRequest {
        $now ??= CarbonImmutable::now($station->getTimezoneObject());

        // Look up all requests that have at least waited as long as the threshold.
        $requests = $this->em->createQuery(
            <<<'DQL'
                SELECT sr, sm
                FROM App\Entity\StationRequest sr JOIN sr.track sm
                WHERE sr.played_at = 0
                AND sr.station = :station
                ORDER BY sr.skip_delay DESC, sr.id ASC
            DQL
        )->setParameter('station', $station)
            ->execute();

        foreach ($requests as $request) {
            /** @var StationRequest $request */
            if ($request->shouldPlayNow($now) && !$this->hasPlayedRecently($request->getTrack(), $station)) {
                return $request;
            }
        }

        return null;
    }

    /**
     * Check the most recent song history.
     */
    public function hasPlayedRecently(StationMedia $media, Station $station): bool
    {
        $lastPlayThresholdMins = ($station->getRequestThreshold() ?? 15);

        if (0 === $lastPlayThresholdMins) {
            return false;
        }

        $lastPlayThreshold = time() - ($lastPlayThresholdMins * 60);

        $recentTracks = $this->em->createQuery(
            <<<'DQL'
                SELECT sh FROM App\Entity\SongHistory sh
                WHERE sh.station = :station
                AND sh.timestamp_start >= :threshold
                ORDER BY sh.timestamp_start DESC
            DQL
        )->setParameter('station', $station)
            ->setParameter('threshold', $lastPlayThreshold)
            ->getArrayResult();

        $eligibleTrack = new StationPlaylistQueue();
        $eligibleTrack->media_id = $media->getIdRequired();
        $eligibleTrack->song_id = $media->getSongId();
        $eligibleTrack->title = $media->getTitle() ?? '';
        $eligibleTrack->artist = $media->getArtist() ?? '';

        return (null === $this->duplicatePrevention->getDistinctTrack([$eligibleTrack], $recentTracks));
    }
}
