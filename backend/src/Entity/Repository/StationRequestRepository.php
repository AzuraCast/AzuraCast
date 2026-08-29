<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Api\StationPlaylistQueue;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationRequest;
use App\Radio\AutoDJ;
use App\Utilities\Time;
use DateTimeImmutable;
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
                AND sr.played_at IS NULL
            DQL
        )->setParameter('station', $station)
            ->execute();
    }

    /**
     * Check if the song is already enqueued as a request.
     */
    public function isTrackPending(StationMedia $media, Station $station): bool
    {
        $pendingRequestThreshold = Time::nowUtc()->subMinutes(10);

        try {
            $pendingRequest = $this->em->createQuery(
                <<<'DQL'
                    SELECT sr.timestamp
                    FROM App\Entity\StationRequest sr
                    WHERE sr.track = :track
                    AND sr.station = :station
                    AND (sr.timestamp >= :threshold OR sr.played_at IS NULL)
                    ORDER BY sr.timestamp DESC
                DQL
            )->setParameter('track', $media)
                ->setParameter('station', $station)
                ->setParameter('threshold', $pendingRequestThreshold)
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (PhpException) {
            return false;
        }

        return ($pendingRequest > 0);
    }

    /**
     * All currently playable requests in playout order.
     *
     * @param mixed[] $additionalSongHistory Additional history rows that requests must not duplicate
     *
     * @return list<StationRequest>
     */
    public function getPlayableRequests(
        Station $station,
        ?DateTimeImmutable $now = null,
        array $additionalSongHistory = []
    ): array {
        $tz = $station->getTimezoneObject();
        $now = Time::nowInTimezone($tz, $now);

        // Look up all requests that have at least waited as long as the threshold.
        $requests = $this->em->createQuery(
            <<<'DQL'
                SELECT sr, sm
                FROM App\Entity\StationRequest sr JOIN sr.track sm
                WHERE sr.played_at IS NULL
                AND sr.station = :station
                ORDER BY sr.skip_delay DESC, sr.id ASC
            DQL
        )->setParameter('station', $station)
            ->execute();

        $recentlyPlayed = $this->getRecentlyPlayedTracks($station);

        return array_values(array_filter(
            $requests,
            fn(StationRequest $request): bool => $request->shouldPlayNow($now)
                && !$this->isDuplicateOfPlayedTrack($request->track, $recentlyPlayed)
                && !$this->isDuplicateOfPlayedTrack($request->track, $additionalSongHistory)
        ));
    }

    /**
     * @param mixed[] $additionalSongHistory Additional history rows that requests must not duplicate
     */
    public function getNextPlayableRequest(
        Station $station,
        ?DateTimeImmutable $now = null,
        array $additionalSongHistory = []
    ): ?StationRequest {
        return $this->getPlayableRequests($station, $now, $additionalSongHistory)[0] ?? null;
    }

    /**
     * Check the most recent song history.
     */
    public function hasPlayedRecently(StationMedia $media, Station $station): bool
    {
        return $this->isDuplicateOfPlayedTrack($media, $this->getRecentlyPlayedTracks($station));
    }

    /**
     * Song history rows within the station's request threshold.
     *
     * @return mixed[]
     */
    private function getRecentlyPlayedTracks(Station $station): array
    {
        $lastPlayThresholdMins = $station->request_threshold ?? 15;

        if ($lastPlayThresholdMins === 0) {
            return [];
        }

        $lastPlayThreshold = Time::nowUtc()->subMinutes($lastPlayThresholdMins);

        return $this->em->createQuery(
            <<<'DQL'
                SELECT sh FROM App\Entity\SongHistory sh
                WHERE sh.station = :station
                AND sh.timestamp_start >= :threshold
                ORDER BY sh.timestamp_start DESC
            DQL
        )->setParameter('station', $station)
            ->setParameter('threshold', $lastPlayThreshold)
            ->getArrayResult();
    }

    /**
     * @param mixed[] $playedTracks
     */
    private function isDuplicateOfPlayedTrack(StationMedia $media, array $playedTracks): bool
    {
        if ($playedTracks === []) {
            return false;
        }

        $eligibleTrack = new StationPlaylistQueue();
        $eligibleTrack->media_id = $media->id;
        $eligibleTrack->song_id = $media->song_id;
        $eligibleTrack->title = $media->title ?? '';
        $eligibleTrack->artist = $media->artist ?? '';

        return $this->duplicatePrevention->getDistinctTrack([$eligibleTrack], $playedTracks) === null;
    }
}
