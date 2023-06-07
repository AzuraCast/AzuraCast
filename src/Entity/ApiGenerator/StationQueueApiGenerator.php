<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use Psr\Http\Message\UriInterface;
use App\Entity\StationQueue;
use App\Entity\StationPlaylist;

final class StationQueueApiGenerator
{
    public function __construct(
        private readonly SongApiGenerator $songApiGenerator
    ) {
    }

    public function __invoke(
        StationQueue $record,
        ?UriInterface $baseUri = null,
        bool $allowRemoteArt = false
    ): \App\Entity\Api\NowPlaying\StationQueue {
        $response = new \App\Entity\Api\NowPlaying\StationQueue();
        $response->cued_at = $record->getTimestampCued();
        $response->played_at = $record->getTimestampPlayed();
        $response->duration = (int)$record->getDuration();
        $response->is_request = $record->getRequest() !== null;

        if ($record->getPlaylist() instanceof StationPlaylist) {
            $response->playlist = $record->getPlaylist()->getName();
        } else {
            $response->playlist = '';
        }

        $recordMedia = $record->getMedia();
        if (null !== $recordMedia) {
            $response->song = ($this->songApiGenerator)(
                song: $recordMedia,
                station: $record->getStation(),
                baseUri: $baseUri,
                allowRemoteArt: $allowRemoteArt
            );
        } else {
            $response->song = ($this->songApiGenerator)(
                song: $record,
                station: $record->getStation(),
                baseUri: $baseUri,
                allowRemoteArt: $allowRemoteArt
            );
        }

        return $response;
    }
}
