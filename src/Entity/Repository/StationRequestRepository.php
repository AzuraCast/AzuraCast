<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Api\StationPlaylistQueue;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationRequest;
use App\Enums\StationFeatures;
use App\Exception;
use App\Radio\AutoDJ;
use App\Radio\Frontend\Blocklist\BlocklistParser;
use App\Service\DeviceDetector;
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
        private readonly StationMediaRepository $mediaRepo,
        private readonly DeviceDetector $deviceDetector,
        private readonly BlocklistParser $blocklistParser,
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

    public function submit(
        Station $station,
        string $trackId,
        bool $isAuthenticated,
        string $ip,
        string $userAgent
    ): int {
        // Verify that the station supports requests.
        StationFeatures::Requests->assertSupportedForStation($station);

        // Forbid web crawlers from using this feature.
        $dd = $this->deviceDetector->parse($userAgent);

        if ($dd->isBot) {
            throw Exception\CannotCompleteActionException::submitRequest(
                __('Search engine crawlers are not permitted to use this feature.')
            );
        }

        // Check frontend blocklist and apply it to requests.
        if (!$this->blocklistParser->isAllowed($station, $ip, $userAgent)) {
            throw Exception\CannotCompleteActionException::submitRequest(
                __('You are not permitted to submit requests.')
            );
        }

        // Verify that Track ID exists with station.
        $mediaItem = $this->mediaRepo->requireByUniqueId($trackId, $station);

        if (!$mediaItem->isRequestable()) {
            throw Exception\CannotCompleteActionException::submitRequest(
                __('This track is not requestable.')
            );
        }

        // Check if the song is already enqueued as a request.
        $this->checkPendingRequest($mediaItem, $station);

        // Check the most recent song history.
        $this->checkRecentPlay($mediaItem, $station);

        if (!$isAuthenticated) {
            // Check for any request (on any station) within the last $threshold_seconds.
            $thresholdMins = $station->getRequestDelay() ?? 5;
            $thresholdSeconds = $thresholdMins * 60;

            // Always have a minimum threshold to avoid flooding.
            if ($thresholdSeconds < 60) {
                $thresholdSeconds = 15;
            }

            $recentRequests = (int)$this->em->createQuery(
                <<<'DQL'
                    SELECT COUNT(sr.id) FROM App\Entity\StationRequest sr
                    WHERE sr.ip = :user_ip
                    AND sr.timestamp >= :threshold
                DQL
            )->setParameter('user_ip', $ip)
                ->setParameter('threshold', time() - $thresholdSeconds)
                ->getSingleScalarResult();

            if ($recentRequests > 0) {
                throw Exception\CannotCompleteActionException::submitRequest(
                    __('You have submitted a request too recently! Please wait before submitting another one.')
                );
            }
        }

        // Save request locally.
        $record = new StationRequest($station, $mediaItem, $ip);
        $this->em->persist($record);
        $this->em->flush();

        return $record->getIdRequired();
    }

    /**
     * Check if the song is already enqueued as a request.
     *
     * @param StationMedia $media
     * @param Station $station
     *
     * @throws Exception
     */
    public function checkPendingRequest(StationMedia $media, Station $station): bool
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
            return true;
        }

        if ($pendingRequest > 0) {
            throw Exception\CannotCompleteActionException::submitRequest(
                __('This song was already requested and will play soon.')
            );
        }

        return true;
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
            if ($request->shouldPlayNow($now)) {
                try {
                    $this->checkRecentPlay($request->getTrack(), $station);
                } catch (PhpException) {
                    continue;
                }

                return $request;
            }
        }

        return null;
    }

    /**
     * Check the most recent song history.
     *
     * @param StationMedia $media
     * @param Station $station
     *
     * @throws Exception
     */
    public function checkRecentPlay(StationMedia $media, Station $station): bool
    {
        $lastPlayThresholdMins = ($station->getRequestThreshold() ?? 15);

        if (0 === $lastPlayThresholdMins) {
            return true;
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

        $isDuplicate = (null === $this->duplicatePrevention->getDistinctTrack([$eligibleTrack], $recentTracks));

        if ($isDuplicate) {
            throw Exception\CannotCompleteActionException::submitRequest(
                __('This song or artist has been played too recently. Wait a while before requesting it again.')
            );
        }

        return true;
    }
}
