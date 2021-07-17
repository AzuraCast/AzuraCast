<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity;
use App\Environment;
use App\Exception;
use App\Radio\AutoDJ;
use App\Service\DeviceDetector;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class StationRequestRepository extends Repository
{
    protected StationMediaRepository $mediaRepo;

    protected DeviceDetector $deviceDetector;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        Environment $environment,
        LoggerInterface $logger,
        StationMediaRepository $mediaRepo,
        DeviceDetector $deviceDetector
    ) {
        parent::__construct($em, $serializer, $environment, $logger);

        $this->mediaRepo = $mediaRepo;
        $this->deviceDetector = $deviceDetector;
    }

    public function submit(
        Entity\Station $station,
        string $trackId,
        bool $isAuthenticated,
        string $ip,
        string $userAgent
    ): int {
        // Forbid web crawlers from using this feature.
        $dd = $this->deviceDetector->parse($userAgent);

        if ($dd->isBot()) {
            throw new Exception(__('Search engine crawlers are not permitted to use this feature.'));
        }

        // Verify that the station supports requests.
        if (!$station->getEnableRequests()) {
            throw new Exception(__('This station does not accept requests currently.'));
        }

        // Verify that Track ID exists with station.
        $media_item = $this->mediaRepo->findByUniqueId($trackId, $station);

        if (!($media_item instanceof Entity\StationMedia)) {
            throw new Exception(__('The song ID you specified could not be found in the station.'));
        }

        if (!$media_item->isRequestable()) {
            throw new Exception(__('The song ID you specified cannot be requested for this station.'));
        }

        // Check if the song is already enqueued as a request.
        $this->checkPendingRequest($media_item, $station);

        // Check the most recent song history.
        $this->checkRecentPlay($media_item, $station);

        if (!$isAuthenticated) {
            // Check for any request (on any station) within the last $threshold_seconds.
            $thresholdMins = $station->getRequestThreshold() ?? 5;
            $thresholdSeconds = $thresholdMins * 60;

            // Always have a minimum threshold to avoid flooding.
            if ($thresholdSeconds < 60) {
                $thresholdSeconds = 15;
            }

            $recent_requests = $this->em->createQuery(
                <<<'DQL'
                    SELECT sr FROM App\Entity\StationRequest sr
                    WHERE sr.ip = :user_ip
                    AND sr.timestamp >= :threshold
                DQL
            )->setParameter('user_ip', $ip)
                ->setParameter('threshold', time() - $thresholdSeconds)
                ->getArrayResult();

            if (count($recent_requests) > 0) {
                throw new Exception(
                    __(
                        'You have submitted a request too recently! Please wait before submitting another one.'
                    )
                );
            }
        }

        // Save request locally.
        $record = new Entity\StationRequest($station, $media_item, $ip);
        $this->em->persist($record);
        $this->em->flush();

        return $record->getIdRequired();
    }

    /**
     * Check if the song is already enqueued as a request.
     *
     * @param Entity\StationMedia $media
     * @param Entity\Station $station
     *
     * @throws Exception
     */
    public function checkPendingRequest(Entity\StationMedia $media, Entity\Station $station): bool
    {
        $pending_request_threshold = time() - (60 * 10);

        try {
            $pending_request = $this->em->createQuery(
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
                ->setParameter('threshold', $pending_request_threshold)
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (\Exception) {
            return true;
        }

        if ($pending_request > 0) {
            throw new Exception(__('Duplicate request: this song was already requested and will play soon.'));
        }

        return true;
    }

    public function getNextPlayableRequest(
        Entity\Station $station,
        ?CarbonInterface $now = null
    ): ?Entity\StationRequest {
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
            /** @var Entity\StationRequest $request */
            if ($request->shouldPlayNow($now)) {
                try {
                    $this->checkRecentPlay($request->getTrack(), $station);
                } catch (\Exception) {
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
     * @param Entity\StationMedia $media
     * @param Entity\Station $station
     *
     * @throws Exception
     */
    public function checkRecentPlay(Entity\StationMedia $media, Entity\Station $station): bool
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

        $eligibleTrack = new Entity\Api\StationPlaylistQueue();
        $eligibleTrack->media_id = $media->getIdRequired();
        $eligibleTrack->song_id = $media->getSongId();
        $eligibleTrack->title = $media->getTitle() ?? '';
        $eligibleTrack->artist = $media->getArtist() ?? '';

        $isDuplicate = (null === AutoDJ\Queue::getDistinctTrack([$eligibleTrack], $recentTracks));

        if ($isDuplicate) {
            throw new Exception(
                __(
                    'This song or artist has been played too recently. Wait a while before requesting it again.'
                )
            );
        }

        return true;
    }
}
