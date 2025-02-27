<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying;

use App\Entity\Api\ResolvableUrl;
use DeepCopy\DeepCopy;
use DeepCopy\Filter\ReplaceFilter;
use DeepCopy\Matcher\PropertyTypeMatcher;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UriInterface;

#[OA\Schema(
    schema: 'Api_NowPlaying',
    required: ['*'],
    type: 'object'
)]
class NowPlaying
{
    #[OA\Property]
    public Station $station;

    #[OA\Property]
    public Listeners $listeners;

    #[OA\Property]
    public Live $live;

    #[OA\Property]
    public ?CurrentSong $now_playing = null;

    #[OA\Property]
    public ?StationQueue $playing_next = null;

    /** @var SongHistory[] */
    #[OA\Property]
    public array $song_history = [];

    #[OA\Property(
        description: 'Whether the stream is currently online.',
        example: true
    )]
    public bool $is_online = false;

    #[OA\Property(
        description: 'Debugging information about where the now playing data comes from.',
        enum: ['hit', 'database', 'station']
    )]
    public ?string $cache = null;

    /**
     * Update any variable items in the feed.
     */
    public function update(): void
    {
        $this->now_playing?->recalculate();
    }

    public function withResolvedUrls(?UriInterface $base = null): static
    {
        $copier = new DeepCopy();

        $copier->addFilter(
            new ReplaceFilter(
                fn(ResolvableUrl $url) => new ResolvableUrl($url->resolveUrl($base)),
            ),
            new PropertyTypeMatcher(ResolvableUrl::class)
        );

        return $copier->copy($this);
    }
}
