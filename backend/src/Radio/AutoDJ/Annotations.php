<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationMediaMetadata;
use App\Entity\StationQueue;
use App\Entity\StationRequest;
use App\Event\Radio\AnnotateNextSong;
use App\Radio\Backend\Liquidsoap\ConfigWriter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class Annotations implements EventSubscriberInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StationQueueRepository $queueRepo,
        private readonly EventDispatcherInterface $eventDispatcher,
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
                ['annotatePlaylist', 10],
                ['annotateRequest', 5],
                ['enableAutoCue', -5],
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
    ): string|bool {
        $queueRow = $this->queueRepo->getNextToSendToAutoDj($station);

        if (null === $queueRow) {
            return false;
        }

        $event = AnnotateNextSong::fromStationQueue($queueRow, $asAutoDj);
        $this->eventDispatcher->dispatch($event);

        return $event->buildAnnotations();
    }

    public function annotateSongPath(AnnotateNextSong $event): void
    {
        $media = $event->getMedia();
        if ($media instanceof StationMedia) {
            $event->setSongPath('media:' . ltrim($media->getPath(), '/'));
        } else {
            $queue = $event->getQueue();
            if ($queue instanceof StationQueue) {
                $customUri = $queue->getAutodjCustomUri();
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
        if (!$station->getBackendType()->isEnabled()) {
            return;
        }

        $annotations = [];
        $annotationsRaw = array_filter([
            'title' => $media->getTitle(),
            'artist' => $media->getArtist(),
            'duration' => $media->getLength(),
            'song_id' => $media->getSongId(),
            'media_id' => $media->getId(),
            ...$media->getExtraMetadata()->toArray(),
        ], fn ($row) => ('' !== $row && null !== $row));

        // Safety checks for cue lengths.
        if (
            isset($annotationsRaw[StationMediaMetadata::CUE_OUT])
            && $annotationsRaw[StationMediaMetadata::CUE_OUT] < 0
        ) {
            $cueOut = abs($annotationsRaw[StationMediaMetadata::CUE_OUT]);

            if (0.0 === $cueOut) {
                unset($annotationsRaw[StationMediaMetadata::CUE_OUT]);
            }

            if (isset($annotationsRaw['duration'])) {
                if ($cueOut > $annotationsRaw['duration']) {
                    unset($annotationsRaw[StationMediaMetadata::CUE_OUT]);
                } else {
                    $annotationsRaw[StationMediaMetadata::CUE_OUT] = max(0, $annotationsRaw['duration'] - $cueOut);
                }
            }
        }

        if (
            isset($annotationsRaw[StationMediaMetadata::CUE_OUT], $annotationsRaw['duration'])
            && $annotationsRaw[StationMediaMetadata::CUE_OUT] > $annotationsRaw['duration']
        ) {
            unset($annotationsRaw[StationMediaMetadata::CUE_OUT]);
        }

        if (
            isset($annotationsRaw[StationMediaMetadata::CUE_IN], $annotationsRaw['duration'])
            && $annotationsRaw[StationMediaMetadata::CUE_IN] > $annotationsRaw['duration']
        ) {
            unset($annotationsRaw[StationMediaMetadata::CUE_IN]);
        }

        foreach ($annotationsRaw as $name => $prop) {
            $prop = ConfigWriter::annotateString((string)$prop);

            if ('duration' === $name) {
                $prop = ConfigWriter::toFloat($prop);
            }

            // Process Liquidsoap-specific annotations.
            if (StationMediaMetadata::isLiquidsoapAnnotation($name)) {
                $prop = match ($name) {
                    'liq_blank_skipped',
                    'liq_cue_file',
                    'liq_longtail',
                    'liq_sustained_ending'
                        => ConfigWriter::toBool($prop),
                    'liq_amplify' => $prop . ' dB',
                    default => ConfigWriter::valueToString($prop)
                };
            }

            $annotations[$name] = $prop;
        }

        $event->addAnnotations($annotations);
    }

    public function annotatePlaylist(AnnotateNextSong $event): void
    {
        $playlist = $event->getPlaylist();
        if (null === $playlist) {
            return;
        }

        $event->addAnnotations([
            'playlist_id' => $playlist->getId(),
        ]);

        if ($playlist->getIsJingle()) {
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
                'request_id' => $request->getId(),
            ]);
        }
    }

    public function enableAutoCue(AnnotateNextSong $event): void
    {
        if ($event->getStation()->getBackendConfig()->getEnableAutoCue()) {
            $event->setProtocol('autocue');
        }
    }

    public function postAnnotation(AnnotateNextSong $event): void
    {
        if (!$event->isAsAutoDj()) {
            return;
        }

        $queueRow = $event->getQueue();
        if ($queueRow instanceof StationQueue) {
            $queueRow->setSentToAutodj();
            $queueRow->setTimestampCued(time());
            $this->em->persist($queueRow);
            $this->em->flush();
        }
    }
}
