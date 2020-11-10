<?php

namespace App\Radio\AutoDJ;

use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Flysystem\FilesystemManager;
use App\Radio\Adapters;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Annotations implements EventSubscriberInterface
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\StationQueueRepository $queueRepo;

    protected Entity\Repository\StationStreamerRepository $streamerRepo;

    protected FilesystemManager $filesystem;

    protected Adapters $adapters;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\StationQueueRepository $queueRepo,
        Entity\Repository\StationStreamerRepository $streamerRepo,
        FilesystemManager $filesystem,
        Adapters $adapters
    ) {
        $this->em = $em;
        $this->queueRepo = $queueRepo;
        $this->streamerRepo = $streamerRepo;
        $this->filesystem = $filesystem;
        $this->adapters = $adapters;
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

    public function annotateSongPath(AnnotateNextSong $event): void
    {
        $media = $event->getMedia();
        if ($media instanceof Entity\StationMedia) {
            $fs = $this->filesystem->getForStation($event->getStation());

            $localMediaPath = $fs->getLocalPath($media->getPathUri());
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
        if ($playlist instanceof Entity\StationPlaylist) {
            // Handle "Jingle mode" by sending the same metadata as the previous song.
            if ($playlist->isJingle()) {
                $event->addAnnotations([
                    'jingle_mode' => 'true',
                ]);

                $np = $event->getStation()->getNowplaying();
                if ($np instanceof Entity\Api\NowPlaying) {
                    $event->addAnnotations([
                        'title' => $np->now_playing->song->title,
                        'artist' => $np->now_playing->song->artist,
                        'playlist_id' => null,
                        'media_id' => null,
                    ]);
                }
            } else {
                $event->addAnnotations([
                    'playlist_id' => $playlist->getId(),
                ]);
            }
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
                $this->queueRepo->newRecordSentToAutoDj($queueRow);
            }

            // The "get next song" function is only called when a streamer is not live.
            $this->streamerRepo->onDisconnect($event->getStation());
        }

        $this->em->flush();
    }
}
