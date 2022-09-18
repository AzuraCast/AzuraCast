<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Radio\Backend\Liquidsoap\ConfigWriter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class Annotations implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Entity\Repository\StationQueueRepository $queueRepo,
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
                ['postAnnotation', -10],
            ],
        ];
    }

    /**
     * Pulls the next song from the AutoDJ, dispatches the AnnotateNextSong event and returns the built result.
     */
    public function annotateNextSong(
        Entity\Station $station,
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
        if ($media instanceof Entity\StationMedia) {
            $event->setSongPath('media:' . ltrim($media->getPath(), '/'));
        } else {
            $queue = $event->getQueue();
            if ($queue instanceof Entity\StationQueue) {
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
        if (!$station->getBackendTypeEnum()->isEnabled()) {
            return;
        }

        $backendConfig = $station->getBackendConfig();

        $annotations = [];
        $annotationsRaw = [
            'title' => $media->getTitle(),
            'artist' => $media->getArtist(),
            'duration' => $media->getLength(),
            'song_id' => $media->getSongId(),
            'media_id' => $media->getId(),
            'liq_amplify' => $media->getAmplify() ?? 0.0,
            'liq_cross_duration' => $media->getFadeOverlap() ?? $backendConfig->getCrossfadeDuration(),
            'liq_fade_in' => $media->getFadeIn() ?? $backendConfig->getCrossfade(),
            'liq_fade_out' => $media->getFadeOut() ?? $backendConfig->getCrossfade(),
            'liq_cue_in' => $media->getCueIn(),
            'liq_cue_out' => $media->getCueOut(),
        ];

        // Safety checks for cue lengths.
        if ($annotationsRaw['liq_cue_out'] < 0) {
            $cue_out = abs($annotationsRaw['liq_cue_out']);
            if (0.0 === $cue_out || $cue_out > $annotationsRaw['duration']) {
                $annotationsRaw['liq_cue_out'] = null;
            } else {
                $annotationsRaw['liq_cue_out'] = max(0, $annotationsRaw['duration'] - $cue_out);
            }
        }
        if ($annotationsRaw['liq_cue_out'] > $annotationsRaw['duration']) {
            $annotationsRaw['liq_cue_out'] = null;
        }
        if ($annotationsRaw['liq_cue_in'] > $annotationsRaw['duration']) {
            $annotationsRaw['liq_cue_in'] = null;
        }

        foreach ($annotationsRaw as $name => $prop) {
            if (null === $prop) {
                continue;
            }

            $prop = ConfigWriter::annotateString((string)$prop);

            // Convert Liquidsoap-specific annotations to floats.
            if ('duration' === $name || str_starts_with($name, 'liq')) {
                $prop = ConfigWriter::toFloat($prop);
            }

            if ('liq_amplify' === $name) {
                $prop .= 'dB';
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
        if ($request instanceof Entity\StationRequest) {
            $event->addAnnotations([
                'request_id' => $request->getId(),
            ]);
        }
    }

    public function postAnnotation(AnnotateNextSong $event): void
    {
        if (!$event->isAsAutoDj()) {
            return;
        }

        $queueRow = $event->getQueue();
        if ($queueRow instanceof Entity\StationQueue) {
            $queueRow->setSentToAutodj();
            $queueRow->setTimestampCued(time());
            $this->em->persist($queueRow);
            $this->em->flush();
        }
    }
}
