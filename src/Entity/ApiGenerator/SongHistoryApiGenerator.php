<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity;
use Psr\Http\Message\UriInterface;

class SongHistoryApiGenerator
{
    protected SongApiGenerator $songApiGenerator;

    public function __construct(SongApiGenerator $songApiGenerator)
    {
        $this->songApiGenerator = $songApiGenerator;
    }

    public function __invoke(
        Entity\SongHistory $record,
        ?UriInterface $baseUri = null,
        bool $allowRemoteArt = false
    ): Entity\Api\SongHistory {
        $response = new Entity\Api\SongHistory();
        $response->sh_id = $record->getIdRequired();
        $response->played_at = (0 === $record->getTimestampStart())
            ? 0
            : $record->getTimestampStart() + Entity\SongHistory::PLAYBACK_DELAY_SECONDS;
        $response->duration = (int)$record->getDuration();
        $response->is_request = ($record->getRequest() !== null);
        if ($record->getPlaylist() instanceof Entity\StationPlaylist) {
            $response->playlist = $record->getPlaylist()->getName();
        } else {
            $response->playlist = '';
        }

        if ($record->getStreamer() instanceof Entity\StationStreamer) {
            $response->streamer = $record->getStreamer()->getDisplayName();
        } else {
            $response->streamer = '';
        }

        if (null !== $record->getMedia()) {
            $response->song = ($this->songApiGenerator)(
                $record->getMedia(),
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

    /**
     * @param Entity\SongHistory[] $records
     * @param UriInterface|null $baseUri
     * @param bool $allowRemoteArt
     *
     * @return Entity\Api\SongHistory[]
     */
    public function fromArray(
        array $records,
        ?UriInterface $baseUri = null,
        bool $allowRemoteArt = false
    ): array {
        $apiRecords = [];
        foreach ($records as $record) {
            $apiRecords[] = ($this)($record, $baseUri, $allowRemoteArt);
        }
        return $apiRecords;
    }

    public function detailed(Entity\SongHistory $record, ?UriInterface $baseUri = null): Entity\Api\DetailedSongHistory
    {
        $apiHistory = ($this)($record, $baseUri);
        $response = new Entity\Api\DetailedSongHistory();
        $response->fromParentObject($apiHistory);
        $response->listeners_start = (int)$record->getListenersStart();
        $response->listeners_end = (int)$record->getListenersEnd();
        $response->delta_total = $record->getDeltaTotal();
        return $response;
    }
}
