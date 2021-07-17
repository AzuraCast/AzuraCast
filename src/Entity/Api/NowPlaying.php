<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Annotations as OA;
use Psr\Http\Message\UriInterface;

/**
 * @OA\Schema(type="object", schema="Api_NowPlaying")
 */
class NowPlaying implements ResolvableUrlInterface
{
    /**
     * Station
     *
     * @OA\Property
     * @var Station
     */
    public Station $station;

    /**
     * Listener details
     *
     * @OA\Property
     * @var NowPlayingListeners
     */
    public NowPlayingListeners $listeners;

    /**
     * Live broadcast details
     *
     * @OA\Property
     * @var NowPlayingLive
     */
    public NowPlayingLive $live;

    /**
     * Current Song
     *
     * @OA\Property
     * @var NowPlayingCurrentSong|null
     */
    public ?NowPlayingCurrentSong $now_playing = null;

    /**
     * Next Playing Song
     *
     * @OA\Property
     * @var StationQueue|null
     */
    public ?StationQueue $playing_next = null;

    /**
     * @OA\Property
     * @var SongHistory[]
     */
    public array $song_history = [];

    /**
     * Whether the stream is currently online.
     *
     * @OA\Property(example=true)
     * @var bool
     */
    public bool $is_online = false;

    /**
     * Debugging information about where the now playing data comes from.
     *
     * @OA\Property(enum={"hit", "database", "station"})
     * @var string|null
     */
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
        if ($this->station instanceof ResolvableUrlInterface) {
            $this->station->resolveUrls($base);
        }

        if ($this->now_playing instanceof ResolvableUrlInterface) {
            $this->now_playing->resolveUrls($base);
        }

        if ($this->playing_next instanceof ResolvableUrlInterface) {
            $this->playing_next->resolveUrls($base);
        }

        foreach ($this->song_history as $history_obj) {
            if ($history_obj instanceof ResolvableUrlInterface) {
                $history_obj->resolveUrls($base);
            }
        }
    }
}
