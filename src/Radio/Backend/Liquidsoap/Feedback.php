<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap;

use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBus;

class Feedback
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\StationQueueRepository $queueRepo,
        protected MessageBus $messageBus
    ) {
    }

    /**
     * Queue an individual station for processing its "Now Playing" metadata.
     *
     * @param Entity\Station $station
     * @param array $extraMetadata
     */
    public function __invoke(Entity\Station $station, array $extraMetadata = []): void
    {
        // Process extra metadata sent by Liquidsoap (if it exists).
        if (!empty($extraMetadata['media_id'])) {
            $media = $this->em->find(Entity\StationMedia::class, $extraMetadata['media_id']);
            if (!$media instanceof Entity\StationMedia) {
                return;
            }

            $sq = $this->queueRepo->findRecentlyCuedSong($station, $media);

            if (!$sq instanceof Entity\StationQueue) {
                $sq = Entity\StationQueue::fromMedia($station, $media);
            } elseif (null === $sq->getMedia()) {
                $sq->setMedia($media);
            }

            if (!empty($extraMetadata['playlist_id']) && null === $sq->getPlaylist()) {
                $playlist = $this->em->find(Entity\StationPlaylist::class, $extraMetadata['playlist_id']);
                if ($playlist instanceof Entity\StationPlaylist) {
                    $sq->setPlaylist($playlist);
                }
            }

            $sq->setSentToAutodj();
            $sq->setTimestampPlayed(time());

            $this->em->persist($sq);
            $this->em->flush();
        }
        /*
        // Trigger a delayed Now Playing update.
        $message = new Message\UpdateNowPlayingMessage();
        $message->station_id = $station->getIdRequired();

        $this->messageBus->dispatch(
            $message,
            [
                new DelayStamp(2000),
            ]
        );
        */
    }
}
