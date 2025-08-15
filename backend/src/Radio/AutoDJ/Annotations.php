<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Cache\AutoCueCache;
use App\Container\EntityManagerAwareTrait;
use App\Entity\Repository\CustomFieldRepository;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationMediaMetadata as Meta;
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

        // If cue_out is negative, it's relative to the end of the track; recompute to be relative to the start.
        if (
            isset($annotations[Meta::CUE_OUT])
            && $annotations[Meta::CUE_OUT] < 0.0
        ) {
            $cueOut = abs($annotations[Meta::CUE_OUT]);

            if (0.0 === $cueOut) {
                unset($annotations[Meta::CUE_OUT]);
            }

            if ($cueOut > $duration) {
                unset($annotations[Meta::CUE_OUT]);
            } else {
                $annotations[Meta::CUE_OUT] = max(0, $duration - $cueOut);
            }
        }

        // cue_out must be less than track duration.
        if (
            isset($annotations[Meta::CUE_OUT])
            && $annotations[Meta::CUE_OUT] > $duration
        ) {
            unset($annotations[Meta::CUE_OUT]);
        }

        // cue_in must be less than track duration.
        if (
            isset($annotations[Meta::CUE_IN])
            && $annotations[Meta::CUE_IN] > $duration
        ) {
            unset($annotations[Meta::CUE_IN]);
        }

        if (0 === count($annotations)) {
            return [];
        }

        // Liquidsoap expects amplify to be in dB.
        if (isset($annotations[Meta::AMPLIFY])) {
            $annotations[Meta::AMPLIFY] .= ' dB';

            // If only amplify is specified, return just it to use it in other AutoCue/amplify functions.
            if (1 === count($annotations)) {
                return [
                    'liq_amplify' => $annotations[Meta::AMPLIFY],
                ];
            }
        }

        // Ensure default values for all annotations.
        $annotations[Meta::CUE_IN] ??= 0.0;
        $annotations[Meta::CUE_OUT] ??= $duration;

        // cue_out must always be greater than cue_in.
        if ($annotations[Meta::CUE_OUT] < $annotations[Meta::CUE_IN]) {
            $annotations[Meta::CUE_IN] = 0.0;
            $annotations[Meta::CUE_OUT] = $duration;
        }

        // start_next must be between cue_in and cue_out.
        if (isset($annotations[Meta::CROSS_START_NEXT])) {
            $startNext = $annotations[Meta::CROSS_START_NEXT];
            if (
                $startNext < $annotations[Meta::CUE_IN]
                || $startNext > $annotations[Meta::CUE_OUT]
            ) {
                unset($annotations[Meta::CROSS_START_NEXT]);
            }
        }

        $backendConfig = $station->backend_config;
        $defaultFade = $backendConfig->isCrossfadeEnabled()
            ? $backendConfig->crossfade
            : 0.0;

        $annotations[Meta::FADE_IN] ??= $defaultFade;
        $annotations[Meta::FADE_OUT] ??= $defaultFade;

        return [
            'azuracast_autocue' => true,
            'liq_amplify' => Types::stringOrNull($annotations[Meta::AMPLIFY] ?? null),
            'autocue_cue_in' => Types::float($annotations[Meta::CUE_IN]),
            'autocue_cue_out' => Types::float($annotations[Meta::CUE_OUT]),
            'autocue_fade_in' => Types::float($annotations[Meta::FADE_IN]),
            'autocue_fade_out' => Types::float($annotations[Meta::FADE_OUT]),
            'autocue_start_next' => Types::floatOrNull(
                $annotations[Meta::CROSS_START_NEXT] ?? null
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
