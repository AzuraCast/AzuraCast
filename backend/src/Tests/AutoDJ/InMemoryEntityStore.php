<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ;

use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistMedia;
use App\Tests\AutoDJ\Scenario\ScenarioRuntime;
use InvalidArgumentException;

/**
 * Set of entities hydrated from a playlist configuration dump with lookup maps
 * the fake repositories / entity manager need to resolve refs and ids
 */
final readonly class InMemoryEntityStore
{
    /**
     * @param array<string, StationPlaylist> $playlistsByRef
     * @param array<string, StationMedia> $mediaByRef
     * @param array<int, StationMedia> $mediaById
     * @param array<int, StationPlaylistMedia> $spmById
     * @param array<int, string> $refByPlaylistId
     */
    public function __construct(
        public Station $station,
        public array $playlistsByRef,
        public array $mediaByRef,
        public array $mediaById,
        public array $spmById,
        public array $refByPlaylistId,
        public ScenarioRuntime $runtime
    ) {
    }

    public function refForPlaylist(StationPlaylist $playlist): ?string
    {
        return $this->refByPlaylistId[$playlist->id] ?? null;
    }

    public function playlistForRef(string $ref): StationPlaylist
    {
        if (!isset($this->playlistsByRef[$ref])) {
            throw new InvalidArgumentException(sprintf('No playlist with ref "%s" in fixture.', $ref));
        }

        return $this->playlistsByRef[$ref];
    }
}
