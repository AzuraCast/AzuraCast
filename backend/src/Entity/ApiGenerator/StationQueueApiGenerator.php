<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity\Api\NowPlaying\StationQueue as NowPlayingStationQueue;
use App\Entity\StationPlaylist;
use App\Entity\StationQueue;
use Psr\Http\Message\UriInterface;

final readonly class StationQueueApiGenerator
{
    public function __construct(
        private SongApiGenerator $songApiGenerator
    ) {
    }

    public function __invoke(
        StationQueue $record,
        ?UriInterface $baseUri = null,
        bool $allowRemoteArt = false
    ): NowPlayingStationQueue {
        $response = new NowPlayingStationQueue();
        $response->cued_at = $record->timestamp_cued->getTimestamp();
        $response->played_at = $record->timestamp_played?->getTimestamp() ?? null;
        $response->duration = $record->duration ?? 0.0;
        $response->is_request = $record->request !== null;

        if ($record->playlist instanceof StationPlaylist) {
            $response->playlist = $record->playlist->name;
        } else {
            $response->playlist = '';
        }

        $recordMedia = $record->media;
        if (null !== $recordMedia) {
            $response->song = ($this->songApiGenerator)(
                song: $recordMedia,
                station: $record->station,
                baseUri: $baseUri,
                allowRemoteArt: $allowRemoteArt
            );
        } else {
            $response->song = ($this->songApiGenerator)(
                song: $record,
                station: $record->station,
                baseUri: $baseUri,
                allowRemoteArt: $allowRemoteArt
            );
        }

        return $response;
    }
}
