<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity;
use App\Http\Router;
use App\Radio\Enums\BackendAdapters;
use Exception;
use GuzzleHttp\Psr7\Uri;
use NowPlaying\Result\CurrentSong;
use NowPlaying\Result\Result;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UriInterface;

class NowPlayingApiGenerator
{
    public function __construct(
        protected SongApiGenerator $songApiGenerator,
        protected SongHistoryApiGenerator $songHistoryApiGenerator,
        protected StationApiGenerator $stationApiGenerator,
        protected StationQueueApiGenerator $stationQueueApiGenerator,
        protected Entity\Repository\SongHistoryRepository $historyRepo,
        protected Entity\Repository\StationQueueRepository $queueRepo,
        protected Entity\Repository\StationStreamerBroadcastRepository $broadcastRepo,
        protected EventDispatcherInterface $eventDispatcher,
        protected Router $router,
    ) {
    }

    public function __invoke(
        Entity\Station $station,
        Result $npResult
    ): Entity\Api\NowPlaying\NowPlaying {
        $baseUri = new Uri('');

        if (empty($npResult->currentSong->text)) {
            return $this->offlineApi($station, $baseUri);
        }

        $npOld = $station->getNowplaying();

        $np = new Entity\Api\NowPlaying\NowPlaying();
        $np->is_online = $npResult->meta->online;
        $np->station = ($this->stationApiGenerator)($station, $baseUri);
        $np->listeners = new Entity\Api\NowPlaying\Listeners(
            total: $npResult->listeners->total,
            unique: $npResult->listeners->unique
        );

        $updateSongFromNowPlaying = (BackendAdapters::Liquidsoap !== $station->getBackendTypeEnum());

        try {
            $sh_obj = $this->historyRepo->updateFromNowPlaying(
                $station,
                $np->listeners->current,
                ($updateSongFromNowPlaying)
                    ? Entity\Song::createFromNowPlayingSong($npResult->currentSong)
                    : null
            );
        } catch (Exception) {
            return $this->offlineApi($station, $baseUri);
        }

        $np->song_history = $this->songHistoryApiGenerator->fromArray(
            $this->historyRepo->getVisibleHistory($station),
            $baseUri,
            true
        );

        $nextVisibleSong = $this->queueRepo->getNextVisible($station);
        if (null === $nextVisibleSong) {
            $np->playing_next = $npOld->playing_next ?? null;
        } else {
            $np->playing_next = ($this->stationQueueApiGenerator)(
                $nextVisibleSong,
                $baseUri,
                true
            );
        }

        // Detect and report live DJ status
        $currentStreamer = $station->getCurrentStreamer();

        if (null !== $currentStreamer) {
            $broadcastStart = $this->broadcastRepo->getLatestBroadcast($station)?->getTimestampStart();

            $live = new Entity\Api\NowPlaying\Live();
            $live->is_live = true;
            $live->streamer_name = $currentStreamer->getDisplayName();
            $live->broadcast_start = $broadcastStart;

            if (0 !== $currentStreamer->getArtUpdatedAt()) {
                $live->art = $this->router->named(
                    route_name: 'api:stations:streamer:art',
                    route_params: [
                        'station_id' => $station->getIdRequired(),
                        'streamer_id' => $currentStreamer->getIdRequired() . '|' . $currentStreamer->getArtUpdatedAt(),
                    ],
                );
            }

            $np->live = $live;
        } else {
            $np->live = new Entity\Api\NowPlaying\Live();
        }

        $apiSongHistory = ($this->songHistoryApiGenerator)(
            record: $sh_obj,
            baseUri: $baseUri,
            allowRemoteArt: true,
            isNowPlaying: true
        );
        $apiCurrentSong = new Entity\Api\NowPlaying\CurrentSong();
        $apiCurrentSong->fromParentObject($apiSongHistory);

        $np->now_playing = $apiCurrentSong;

        $np->update();
        return $np;
    }

    /**
     * If a station doesn't already have a cached "Now Playing", generate a blank one.
     * This is useful for situations that *require* a valid NowPlaying API model, like
     * Vue initial data structures.
     *
     * @param Entity\Station $station
     * @param UriInterface|null $baseUri
     *
     */
    public function currentOrEmpty(
        Entity\Station $station,
        ?UriInterface $baseUri = null
    ): Entity\Api\NowPlaying\NowPlaying {
        $np = $station->getNowplaying();
        return $np ?? $this->offlineApi($station, $baseUri);
    }

    protected function offlineApi(
        Entity\Station $station,
        ?UriInterface $baseUri = null
    ): Entity\Api\NowPlaying\NowPlaying {
        $np = new Entity\Api\NowPlaying\NowPlaying();

        $np->station = ($this->stationApiGenerator)($station, $baseUri);
        $np->listeners = new Entity\Api\NowPlaying\Listeners();

        $songObj = Entity\Song::createOffline();

        $offlineApiNowPlaying = new Entity\Api\NowPlaying\CurrentSong();
        $offlineApiNowPlaying->sh_id = 0;
        $offlineApiNowPlaying->song = ($this->songApiGenerator)(
            song: $songObj,
            station: $station,
            baseUri: $baseUri
        );
        $np->now_playing = $offlineApiNowPlaying;

        $np->song_history = $this->songHistoryApiGenerator->fromArray(
            $this->historyRepo->getVisibleHistory($station),
            $baseUri,
            true
        );

        $nextVisible = $this->queueRepo->getNextVisible($station);
        if ($nextVisible instanceof Entity\StationQueue) {
            $np->playing_next = ($this->stationQueueApiGenerator)(
                $nextVisible,
                $baseUri,
                true
            );
        }

        $np->live = new Entity\Api\NowPlaying\Live();

        $np->update();
        return $np;
    }

    protected function tracksMatch(
        Entity\Song|array|string|CurrentSong $currentSong,
        ?string $oldSongId
    ): bool {
        $current_song_hash = Entity\Song::getSongHash($currentSong);
        return (0 === strcmp($current_song_hash, $oldSongId ?? ''));
    }
}
