<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Cache\AutoCueCache;
use App\Container\EntityManagerAwareTrait;
use App\Entity\Repository\CustomFieldRepository;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationMediaMetadata;
use App\Entity\StationQueue;
use App\Entity\StationRequest;
use App\Event\Radio\AnnotateNextSong;
use App\Utilities\Time;
use App\Utilities\Types;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class Annotations implements EventSubscriberInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StationQueueRepository $queueRepo,
        private readonly CustomFieldRepository $customFieldRepo,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AutoCueCache $autoCueCache,
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AnnotateNextSong::class => [
                ['annotateSongPath', 20],
                ['annotateForLiquidsoap', 15],
                ['addCachedAutocueData', 12],
                ['annotatePlaylist', 10],
                ['annotateRequest', 5],
                ['postAnnotation', -10],
            ],
        ];
    }

    /**
     * Pulls the next song from the AutoDJ, dispatches the AnnotateNextSong event and returns the built result.
     */
    public function annotateNextSong(
        Station $station,
        bool $asAutoDj = false,
    ): string {
        $queueRow = $this->queueRepo->getNextToSendToAutoDj($station);

        if (null === $queueRow) {
            throw new RuntimeException('Queue is empty!');
        }

        $event = AnnotateNextSong::fromStationQueue($queueRow, $asAutoDj);
        $this->eventDispatcher->dispatch($event);

        return $event->buildAnnotations();
    }

    public function annotateSongPath(AnnotateNextSong $event): void
    {
        $media = $event->getMedia();
        if ($media instanceof StationMedia) {
            $event->setSongPath('media:' . ltrim($media->path, '/'));
        } else {
            $queue = $event->getQueue();
            if ($queue instanceof StationQueue) {
                $customUri = $queue->autodj_custom_uri;
                if (!empty($customUri)) {
                    $event->setSongPath($customUri);
                }
            }
        }
    }

    public function annotateForLiquidsoap(AnnotateNextSong $event): void
    {
        $media = $event->getMedia();
        if (null === $media) {
            return;
        }

        $station = $event->getStation();
        if (!$station->backend_type->isEnabled()) {
            return;
        }

        $duration = $media->length;

        $event->addAnnotations([
            'title' => $media->title,
            'artist' => $media->artist,
            'duration' => $duration,
            'song_id' => $media->song_id,
            'media_id' => $media->id,
            'sq_id' => $event->getQueue()?->id,
            ...$this->processAutocueAnnotations(
                $station,
                $media->extra_metadata->toArray(),
                $duration,
            ),
            ...$this->customFieldRepo->getCustomFields($media),
        ]);
    }

    public function addCachedAutocueData(AnnotateNextSong $event): void
    {
        $media = $event->getMedia();
        if (null === $media) {
            return;
        }

        $station = $event->getStation();
        if (!$station->backend_type->isEnabled()) {
            return;
        }

        $cacheKey = $this->autoCueCache->getCacheKey($media);

        $event->addAnnotations([
            'azuracast_cache_key' => $cacheKey,
            ...$this->processAutocueAnnotations(
                $station,
                $this->autoCueCache->getForCacheKey($cacheKey),
                $media->length
            ),
        ]);
    }

    /**
     * @param null|array<string, mixed> $metadata
     */
    private function processAutocueAnnotations(
        Station $station,
        ?array $metadata,
        float $duration,
    ): array {
        $annotations = array_filter(
            $metadata ?? [],
            fn($row) => $row !== null
        );

        if (0 === count($annotations)) {
            return [];
        }

        // Safety checks for cue lengths.
        if (
            isset($annotations[StationMediaMetadata::CUE_OUT])
            && $annotations[StationMediaMetadata::CUE_OUT] < 0.0
        ) {
            $cueOut = abs($annotations[StationMediaMetadata::CUE_OUT]);

            if (0.0 === $cueOut) {
                unset($annotations[StationMediaMetadata::CUE_OUT]);
            }

            if ($cueOut > $duration) {
                unset($annotations[StationMediaMetadata::CUE_OUT]);
            } else {
                $annotations[StationMediaMetadata::CUE_OUT] = max(0, $duration - $cueOut);
            }
        }

        if (
            isset($annotations[StationMediaMetadata::CUE_OUT])
            && $annotations[StationMediaMetadata::CUE_OUT] > $duration
        ) {
            unset($annotations[StationMediaMetadata::CUE_OUT]);
        }

        if (
            isset($annotations[StationMediaMetadata::CUE_IN])
            && $annotations[StationMediaMetadata::CUE_IN] > $duration
        ) {
            unset($annotations[StationMediaMetadata::CUE_IN]);
        }

        if (0 === count($annotations)) {
            return [];
        }

        // Standardize Amplify metadata in Liquidsoap format.
        if (isset($annotations[StationMediaMetadata::AMPLIFY])) {
            $annotations[StationMediaMetadata::AMPLIFY] .= ' dB';

            // If only amplify is specified, return just it to use it in other AutoCue/amplify functions.
            if (1 === count($annotations)) {
                return [
                    'liq_amplify' => $annotations[StationMediaMetadata::AMPLIFY],
                ];
            }
        }

        // Ensure default values for all annotations.
        $annotations[StationMediaMetadata::CUE_IN] ??= 0.0;
        $annotations[StationMediaMetadata::CUE_OUT] ??= $duration;

        $backendConfig = $station->backend_config;
        $defaultFade = $backendConfig->isCrossfadeEnabled()
            ? $backendConfig->crossfade
            : 0.0;

        $annotations[StationMediaMetadata::FADE_IN] ??= $defaultFade;
        $annotations[StationMediaMetadata::FADE_OUT] ??= $defaultFade;

        return [
            'azuracast_autocue' => true,
            'liq_amplify' => Types::stringOrNull($annotations[StationMediaMetadata::AMPLIFY] ?? null),
            'autocue_cue_in' => Types::float($annotations[StationMediaMetadata::CUE_IN]),
            'autocue_cue_out' => Types::float($annotations[StationMediaMetadata::CUE_OUT]),
            'autocue_fade_in' => Types::float($annotations[StationMediaMetadata::FADE_IN]),
            'autocue_fade_out' => Types::float($annotations[StationMediaMetadata::FADE_OUT]),
            'autocue_start_next' => Types::floatOrNull(
                $annotations[StationMediaMetadata::CROSS_START_NEXT] ?? null
            ),
        ];
    }

    public function annotatePlaylist(AnnotateNextSong $event): void
    {
        $playlist = $event->getPlaylist();
        if (null === $playlist) {
            return;
        }

        $event->addAnnotations([
            'playlist_id' => $playlist->id,
        ]);

        if ($playlist->is_jingle) {
            $event->addAnnotations([
                'jingle_mode' => 'true',
            ]);
        }
    }

    public function annotateRequest(AnnotateNextSong $event): void
    {
        $request = $event->getRequest();
        if ($request instanceof StationRequest) {
            $event->addAnnotations([
                'request_id' => $request->id,
            ]);
        }
    }

    public function postAnnotation(AnnotateNextSong $event): void
    {
        if (!$event->isAsAutoDj()) {
            return;
        }

        $queueRow = $event->getQueue();
        if ($queueRow instanceof StationQueue) {
            $queueRow->sent_to_autodj = true;
            $queueRow->timestamp_cued = Time::nowUtc();
            $this->em->persist($queueRow);
            $this->em->flush();
        }
    }
}
