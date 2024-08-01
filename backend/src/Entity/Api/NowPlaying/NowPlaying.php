<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying;

use App\Entity\Api\ResolvableUrlInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UriInterface;

#[OA\Schema(schema: 'Api_NowPlaying', type: 'object')]
class NowPlaying implements ResolvableUrlInterface
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

    /**
     * Return an array representation of this object.
     *
     * @return mixed[]
     */
    public function toArray(): array
    {
        return json_decode(json_encode($this, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Iterate through sub-items and re-resolve any Uri instances to reflect base URL changes.
     *
     * @param UriInterface $base
     */
    public function resolveUrls(UriInterface $base): void
    {
        $this->station->resolveUrls($base);

        $this->live->resolveUrls($base);

        if ($this->now_playing instanceof ResolvableUrlInterface) {
            $this->now_playing->resolveUrls($base);
        }

        if ($this->playing_next instanceof ResolvableUrlInterface) {
            $this->playing_next->resolveUrls($base);
        }

        foreach ($this->song_history as $historyObj) {
            if ($historyObj instanceof ResolvableUrlInterface) {
                $historyObj->resolveUrls($base);
            }
        }
    }
}
