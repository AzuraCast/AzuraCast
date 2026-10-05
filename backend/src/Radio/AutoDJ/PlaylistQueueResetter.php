<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\Station;
use App\Utilities\Time;

final class PlaylistQueueResetter
{
    public function __construct(
        private readonly StationPlaylistRepository $spRepo,
        private readonly StationPlaylistMediaRepository $spmRepo,
    ) {
    }

    public function resetAllQueues(Station $station): void
    {
        $now = Time::nowUtc();
        $resetSequential = $station->backend_config->reset_sequential_queues_on_restart;

        foreach ($station->playlists as $playlist) {
            if (
                $playlist->preserve_queue_on_restart
                || (!$resetSequential && $playlist->order === PlaylistOrders::Sequential)
            ) {
                continue;
            }

            match ($playlist->source) {
                PlaylistSources::Songs => $this->spmRepo->resetQueue($playlist, $now),
                PlaylistSources::Playlists => $this->spRepo->resetPlaylistGroupQueue($playlist, $now),
                default => null,
            };
        }
    }
}
