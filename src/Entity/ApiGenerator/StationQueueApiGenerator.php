<?php

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

    public function __invoke(Entity\StationQueue $record, ?UriInterface $baseUri = null): Entity\Api\StationQueue
    {
        $response = new Entity\Api\StationQueue();
        $response->cued_at = $record->getTimestampCued();
        $response->duration = (int)$record->getDuration();
        $response->is_request = $record->getRequest() !== null;
        if ($record->getPlaylist() instanceof Entity\StationPlaylist) {
            $response->playlist = $record->getPlaylist()->getName();
        } else {
            $response->playlist = '';
        }

        if ($record->getMedia()) {
            $response->song = ($this->songApiGenerator)($record->getMedia(), $record->getStation(), $baseUri);
        } else {
            $response->song = ($this->songApiGenerator)($record, $record->getStation(), $baseUri);
        }

        return $response;
    }
}
