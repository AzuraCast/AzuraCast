<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity\Api\DetailedSongHistory;
use App\Entity\Api\NowPlaying\SongHistory;
use App\Entity\SongHistory as SongHistoryEntity;
use App\Entity\StationPlaylist;
use App\Entity\StationStreamer;
use Psr\Http\Message\UriInterface;

final class SongHistoryApiGenerator
{
    public function __construct(
        private readonly SongApiGenerator $songApiGenerator
    ) {
    }

    public function __invoke(
        SongHistoryEntity $record,
        ?UriInterface $baseUri = null,
        bool $allowRemoteArt = false,
        bool $isNowPlaying = false,
    ): SongHistory {
        $response = new SongHistory();
        $response->sh_id = $record->getIdRequired();
        $response->played_at = (0 === $record->getTimestampStart())
            ? 0
            : $record->getTimestampStart() + SongHistoryEntity::PLAYBACK_DELAY_SECONDS;
        $response->duration = (int)$record->getDuration();
        $response->is_request = ($record->getRequest() !== null);
        if ($record->getPlaylist() instanceof StationPlaylist) {
            $response->playlist = $record->getPlaylist()->getName();
        } else {
            $response->playlist = '';
        }

        if ($record->getStreamer() instanceof StationStreamer) {
            $response->streamer = $record->getStreamer()->getDisplayName();
        } else {
            $response->streamer = '';
        }

        if (null !== $record->getMedia()) {
            $response->song = ($this->songApiGenerator)(
                song: $record->getMedia(),
                station: $record->getStation(),
                baseUri: $baseUri,
                allowRemoteArt: $allowRemoteArt,
                isNowPlaying: $isNowPlaying
            );
        } else {
            $response->song = ($this->songApiGenerator)(
                song: $record,
                station: $record->getStation(),
                baseUri: $baseUri,
                allowRemoteArt: $allowRemoteArt,
                isNowPlaying: $isNowPlaying
            );
        }

        return $response;
    }

    /**
     * @param SongHistoryEntity[] $records
     * @param UriInterface|null $baseUri
     * @param bool $allowRemoteArt
     *
     * @return SongHistory[]
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

    public function detailed(
        SongHistoryEntity $record,
        ?UriInterface $baseUri = null
    ): DetailedSongHistory {
        $apiHistory = ($this)($record, $baseUri);
        $response = new DetailedSongHistory();
        $response->fromParentObject($apiHistory);
        $response->listeners_start = (int)$record->getListenersStart();
        $response->listeners_end = (int)$record->getListenersEnd();
        $response->delta_total = $record->getDeltaTotal();
        $response->is_visible = $record->getIsVisible();

        return $response;
    }
}
