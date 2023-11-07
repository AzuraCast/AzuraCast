<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Cache\NowPlayingCache;
use App\Container\EntityManagerAwareTrait;
use App\Entity\Repository\SongHistoryRepository;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Song;
use App\Entity\SongHistory;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use Exception;
use RuntimeException;

final class FeedbackCommand extends AbstractCommand
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StationQueueRepository $queueRepo,
        private readonly SongHistoryRepository $historyRepo,
        private readonly NowPlayingCache $nowPlayingCache
    ) {
    }

    protected function doRun(
        Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): bool {
        if (!$asAutoDj) {
            return false;
        }

        // Process extra metadata sent by Liquidsoap (if it exists).
        try {
            $historyRow = $this->getSongHistory($station, $payload);
            $this->em->persist($historyRow);

            $this->historyRepo->changeCurrentSong($station, $historyRow);
            $this->em->flush();

            $this->nowPlayingCache->forceUpdate($station);

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
        Station $station,
        array $payload
    ): SongHistory {
        if (isset($payload['artist'])) {
            $newSong = Song::createFromArray([
                'artist' => $payload['artist'],
                'title' => $payload['title'] ?? '',
            ]);

            if (!$this->historyRepo->isDifferentFromCurrentSong($station, $newSong)) {
                throw new RuntimeException('Song is not different from current song.');
            }

            return new SongHistory(
                $station,
                $newSong
            );
        }

        if (empty($payload['media_id'])) {
            throw new RuntimeException('No payload provided.');
        }

        $media = $this->em->find(StationMedia::class, $payload['media_id']);
        if (!$media instanceof StationMedia) {
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
                $playlist = $this->em->find(StationPlaylist::class, $payload['playlist_id']);
                if ($playlist instanceof StationPlaylist) {
                    $sq->setPlaylist($playlist);
                }
            }

            $this->em->persist($sq);
            $this->em->flush();

            $this->queueRepo->trackPlayed($station, $sq);

            return SongHistory::fromQueue($sq);
        }

        $history = new SongHistory($station, $media);
        $history->setMedia($media);

        if (!empty($payload['playlist_id'])) {
            $playlist = $this->em->find(StationPlaylist::class, $payload['playlist_id']);
            if ($playlist instanceof StationPlaylist) {
                $history->setPlaylist($playlist);
            }
        }

        return $history;
    }
}
