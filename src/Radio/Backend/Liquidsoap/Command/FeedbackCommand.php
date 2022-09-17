<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Monolog\Logger;
use RuntimeException;

final class FeedbackCommand extends AbstractCommand
{
    public function __construct(
        Logger $logger,
        private readonly EntityManagerInterface $em,
        private readonly Entity\Repository\StationQueueRepository $queueRepo,
        private readonly Entity\Repository\SongHistoryRepository $historyRepo
    ) {
        parent::__construct($logger);
    }

    protected function doRun(
        Entity\Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): bool {
        // Process extra metadata sent by Liquidsoap (if it exists).
        try {
            $historyRow = $this->getSongHistory($station, $payload);

            $this->historyRepo->changeCurrentSong($station, $historyRow);

            $this->em->flush();
            return true;
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('Liquidsoap feedback error: %s', $e->getMessage()),
                [
                    'exception' => $e,
                ]
            );

            return false;
        }
    }

    private function getSongHistory(
        Entity\Station $station,
        array $payload
    ): Entity\SongHistory {
        if (isset($payload['artist'])) {
            $newSong = Entity\Song::createFromArray([
                'artist' => $payload['artist'],
                'title' => $payload['title'] ?? '',
            ]);

            if (!$this->historyRepo->isDifferentFromCurrentSong($station, $newSong)) {
                throw new RuntimeException('Song is not different from current song.');
            }

            return new Entity\SongHistory(
                $station,
                $newSong
            );
        }

        if (empty($payload['media_id'])) {
            throw new RuntimeException('No payload provided.');
        }

        $media = $this->em->find(Entity\StationMedia::class, $payload['media_id']);
        if (!$media instanceof Entity\StationMedia) {
            throw new RuntimeException('Media ID does not exist for station.');
        }

        if (!$this->historyRepo->isDifferentFromCurrentSong($station, $media)) {
            throw new RuntimeException('Song is not different from current song.');
        }

        $sq = $this->queueRepo->findRecentlyCuedSong($station, $media);

        if (null !== $sq) {
            // If there's an existing record, ensure it has all the proper metadata.
            if (null === $sq->getMedia()) {
                $sq->setMedia($media);
            }

            if (!empty($payload['playlist_id']) && null === $sq->getPlaylist()) {
                $playlist = $this->em->find(Entity\StationPlaylist::class, $payload['playlist_id']);
                if ($playlist instanceof Entity\StationPlaylist) {
                    $sq->setPlaylist($playlist);
                }
            }

            $this->em->persist($sq);
            $this->em->flush();

            $this->queueRepo->trackPlayed($station, $sq);

            $sh = Entity\SongHistory::fromQueue($sq);
            $this->em->persist($sh);

            return $sh;
        }

        $history = new Entity\SongHistory($station, $media);
        $history->setMedia($media);

        if (!empty($payload['playlist_id'])) {
            $playlist = $this->em->find(Entity\StationPlaylist::class, $payload['playlist_id']);
            if ($playlist instanceof Entity\StationPlaylist) {
                $history->setPlaylist($playlist);
            }
        }

        return $history;
    }
}
