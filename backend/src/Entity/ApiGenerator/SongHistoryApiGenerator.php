<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity\Api\DetailedSongHistory;
use App\Entity\Api\NowPlaying\SongHistory;
use App\Entity\SongHistory as SongHistoryEntity;
use App\Entity\StationPlaylist;
use App\Entity\StationStreamer;
use Carbon\CarbonImmutable;
use Psr\Http\Message\UriInterface;

final readonly class SongHistoryApiGenerator
{
    public function __construct(
        private SongApiGenerator $songApiGenerator
    ) {
    }

    /**
     * @template T of SongHistory
     * @phpstan-param T|null $objectToPopulate
     * @phpstan-return ($objectToPopulate is not null ? T : SongHistory)
     */
    public function __invoke(
        SongHistoryEntity $record,
        ?UriInterface $baseUri = null,
        bool $allowRemoteArt = false,
        bool $isNowPlaying = false,
        ?SongHistory $objectToPopulate = null
    ): SongHistory {
        $response = $objectToPopulate ?? new SongHistory();
        $response->sh_id = $record->id;

        $response->played_at = CarbonImmutable::instance($record->timestamp_start)
            ->addSeconds(SongHistoryEntity::PLAYBACK_DELAY_SECONDS)
            ->getTimestamp();

        $response->duration = (int)$record->duration;
        $response->is_request = ($record->request !== null);
        if ($record->playlist instanceof StationPlaylist) {
            $response->playlist = $record->playlist->name;
        } else {
            $response->playlist = '';
        }

        if ($record->streamer instanceof StationStreamer) {
            $response->streamer = $record->streamer->display_name;
        } else {
            $response->streamer = '';
        }

        if (null !== $record->media) {
            $response->song = ($this->songApiGenerator)(
                song: $record->media,
                station: $record->station,
                baseUri: $baseUri,
                allowRemoteArt: $allowRemoteArt,
                isNowPlaying: $isNowPlaying
            );
        } else {
            $response->song = ($this->songApiGenerator)(
                song: $record,
                station: $record->station,
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
        $response = new DetailedSongHistory();

        $this->__invoke($record, $baseUri, objectToPopulate: $response);

        $response->listeners_start = (int)$record->listeners_start;
        $response->listeners_end = (int)$record->listeners_end;
        $response->delta_total = $record->delta_total;
        $response->is_visible = $record->is_visible;

        return $response;
    }
}
