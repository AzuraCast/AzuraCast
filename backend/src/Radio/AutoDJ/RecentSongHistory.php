<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

/**
 * Recently played tracks that duplicate prevention checks tracks against.
 * Artists can be checked over a different time range than tracks and titles.
 *
 * @phpstan-import-type PlayedTrack from DuplicatePrevention
 */
final readonly class RecentSongHistory
{
    /**
     * @param PlayedTrack[] $playedTracks
     * @param PlayedTrack[] $artistPlayedTracks
     */
    public function __construct(
        public array $playedTracks = [],
        public array $artistPlayedTracks = []
    ) {
    }

    /**
     * @param PlayedTrack $playedTrack
     */
    public function withPlayedTrack(array $playedTrack): self
    {
        return new self(
            [...$this->playedTracks, $playedTrack],
            [...$this->artistPlayedTracks, $playedTrack]
        );
    }
}
