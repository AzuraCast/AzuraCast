<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity;
use Psr\Http\Message\UriInterface;

class StationQueueApiGenerator
{
    protected SongApiGenerator $songApiGenerator;

    public function __construct(SongApiGenerator $songApiGenerator)
    {
        $this->songApiGenerator = $songApiGenerator;
    }

    public function __invoke(
        Entity\StationQueue $record,
        ?UriInterface $baseUri = null,
        bool $allowRemoteArt = false
    ): Entity\Api\StationQueue {
        $response = new Entity\Api\StationQueue();
        $response->cued_at = $record->getTimestampCued();
        $response->duration = (int)$record->getDuration();
        $response->is_request = $record->getRequest() !== null;
        if ($record->getPlaylist() instanceof Entity\StationPlaylist) {
            $response->playlist = $record->getPlaylist()->getName();
        } else {
            $response->playlist = '';
        }

        $recordMedia = $record->getMedia();
        if (null !== $recordMedia) {
            $response->song = ($this->songApiGenerator)(
                $recordMedia,
                $record->getStation(),
                $baseUri,
                $allowRemoteArt
            );
        } else {
            $response->song = ($this->songApiGenerator)(
                $record,
                $record->getStation(),
                $baseUri,
                $allowRemoteArt
            );
        }

        return $response;
    }
}
