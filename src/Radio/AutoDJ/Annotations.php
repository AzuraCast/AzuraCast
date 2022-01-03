<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Flysystem\StationFilesystems;
use App\Radio\Adapters;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Annotations implements EventSubscriberInterface
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\StationQueueRepository $queueRepo,
        protected Entity\Repository\StationStreamerRepository $streamerRepo,
        protected Adapters $adapters,
        protected LoggerInterface $logger,
        protected EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AnnotateNextSong::class => [
                ['annotateSongPath', 15],
                ['annotatePlaylist', 10],
                ['annotateRequest', 5],
                ['postAnnotation', -10],
            ],
        ];
    }

    /**
     * Pulls the next song from the AutoDJ, dispatches the AnnotateNextSong event and returns the built result.
     *
     * @param Entity\Station $station
     * @param bool $asAutoDj
     */
    public function annotateNextSong(
        Entity\Station $station,
        bool $asAutoDj = false,
    ): string {
        $queueRow = $this->queueRepo->getNextToSendToAutoDj($station);

        // Try to rebuild the queue if it's empty.
        if (null === $queueRow) {
            $this->logger->info(
                'Queue is empty!',
                [
                    'station' => [
                        'id' => $station->getId(),
                        'name' => $station->getName(),
                    ],
                ]
            );
            return '';
        }

        $event = new AnnotateNextSong($queueRow, $asAutoDj);
        $this->eventDispatcher->dispatch($event);

        $annotation = $event->buildAnnotations();
        $queueRow->addLogRecord(LogLevel::INFO, 'Annotation: ' . $annotation);
        $this->em->persist($queueRow);
        $this->em->flush();

        return $annotation;
    }

    public function annotateSongPath(AnnotateNextSong $event): void
    {
        $media = $event->getMedia();
        if ($media instanceof Entity\StationMedia) {
            $localMediaPath = (new StationFilesystems($event->getStation()))
                ->getMediaFilesystem()
                ->getLocalPath($media->getPath());

            $event->setSongPath($localMediaPath);

            $backend = $this->adapters->getBackendAdapter($event->getStation());
            $event->addAnnotations($backend->annotateMedia($media));
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

    public function annotatePlaylist(AnnotateNextSong $event): void
    {
        $playlist = $event->getPlaylist();
        if (null === $playlist) {
            return;
        }

        // Handle "Jingle mode" by sending the same metadata as the previous song.
        if ($playlist->getIsJingle()) {
            $event->addAnnotations([
                'jingle_mode' => 'true',
            ]);

            $queue = $event->getQueue();
            if (null !== $queue) {
                $lastVisible = $this->queueRepo->getLatestVisibleRow($event->getStation());

                if (null !== $lastVisible) {
                    $event->addAnnotations(
                        [
                            'title'       => $lastVisible->getTitle(),
                            'artist'      => $lastVisible->getArtist(),
                            'playlist_id' => null,
                            'media_id'    => null,
                        ]
                    );
                }
            }
        } else {
            $event->addAnnotations([
                'playlist_id' => $playlist->getId(),
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
        if ($event->isAsAutoDj()) {
            $queueRow = $event->getQueue();
            if ($queueRow instanceof Entity\StationQueue) {
                $queueRow->setSentToAutodj();
                $queueRow->setTimestampCued(time());
                $this->em->persist($queueRow);
            }

            // The "get next song" function is only called when a streamer is not live.
            $this->streamerRepo->onDisconnect($event->getStation());
        }

        $this->em->flush();
    }
}
