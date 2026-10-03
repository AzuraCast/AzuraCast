<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Simulation;

use App\Entity\StationMedia;
use App\Radio\AutoDJ\DuplicatePrevention;
use App\Tests\AutoDJ\InMemoryEntityStore;
use Closure;
use ReflectionMethod;

/**
 * Which tracks of the dump share an artist with another track
 */
final readonly class SharedArtists
{
    /** @var array<string, bool> */
    private array $sharesArtistByMediaRef;

    public function __construct(InMemoryEntityStore $entities)
    {
        /** @var Closure(?string): string[] $getArtistParts */
        $getArtistParts = (new ReflectionMethod(DuplicatePrevention::class, 'getArtistParts'))
            ->getClosure(new DuplicatePrevention());

        $artistPartsByMediaRef = array_map(
            static fn(StationMedia $media): array => array_values(array_unique($getArtistParts($media->artist))),
            $entities->mediaByRef
        );

        $trackCountByArtistPart = array_count_values(
            array_merge(...array_values($artistPartsByMediaRef))
        );

        $this->sharesArtistByMediaRef = array_map(
            static fn(array $artistParts): bool => array_any(
                $artistParts,
                static fn(string $artistPart): bool => $trackCountByArtistPart[$artistPart] > 1
            ),
            $artistPartsByMediaRef
        );
    }

    public function sharesArtist(string $mediaRef): bool
    {
        return $this->sharesArtistByMediaRef[$mediaRef] ?? false;
    }
}
