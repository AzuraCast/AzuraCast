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
use App\Entity\StationQueue;
use App\Utilities\Types;
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

        // Process Liquidsoap list.assoc to JSON mapping
        $payload = array_map(
            fn($dataVal) => match (true) {
                'true' === $dataVal || 'false' === $dataVal => Types::bool(
                    $dataVal,
                    false,
                    true
                ),
                is_numeric($dataVal) => Types::float($dataVal),
                default => $dataVal
            },
            $payload
        );

        // Process extra metadata sent by Liquidsoap (if it exists).
        $historyRow = $this->getSongHistory($station, $payload);
        $this->em->persist($historyRow);

        $this->historyRepo->changeCurrentSong($station, $historyRow);
        $this->em->flush();

        $this->nowPlayingCache->forceUpdate($station);
        return true;
    }

    private function getSongHistory(
        Station $station,
        array $payload
    ): SongHistory {
        if (empty($payload['media_id'])) {
            if (empty($payload['artist']) && empty($payload['title'])) {
                throw new RuntimeException('No payload provided.');
            }

            $newSong = Song::createFromArray([
                'artist' => $payload['artist'] ?? '',
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

        $media = $this->em->find(StationMedia::class, $payload['media_id']);
        if (!$media instanceof StationMedia) {
            throw new RuntimeException('Media ID does not exist for station.');
        }

        if (!$this->historyRepo->isDifferentFromCurrentSong($station, $media)) {
            throw new RuntimeException('Song is not different from current song.');
        }

        if (!empty($payload['sq_id'])) {
            $sq = $this->em->find(StationQueue::class, $payload['sq_id']);
        } else {
            $sq = $this->queueRepo->findRecentlyCuedSong($station, $media);

            if (null !== $sq) {
                // If there's an existing record, ensure it has all the proper metadata.
                if (null === $sq->media) {
                    $sq->media = $media;
                }

                if (!empty($payload['playlist_id']) && null === $sq->playlist) {
                    $playlist = $this->em->find(StationPlaylist::class, $payload['playlist_id']);
                    if ($playlist instanceof StationPlaylist) {
                        $sq->playlist = $playlist;
                    }
                }

                $this->em->persist($sq);
                $this->em->flush();
            }
        }

        if (null !== $sq) {
            $this->queueRepo->trackPlayed($station, $sq);
            return SongHistory::fromQueue($sq);
        }

        $history = new SongHistory($station, $media);
        $history->media = $media;

        if (!empty($payload['playlist_id'])) {
            $playlist = $this->em->find(StationPlaylist::class, $payload['playlist_id']);
            if ($playlist instanceof StationPlaylist) {
                $history->playlist = $playlist;
            }
        }

        return $history;
    }
}
