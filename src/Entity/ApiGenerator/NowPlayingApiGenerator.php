<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity;
use App\Http\Router;
use App\Utilities\Logger;
use Exception;
use GuzzleHttp\Psr7\Uri;
use NowPlaying\Result\Result;
use Psr\Http\Message\UriInterface;
use RuntimeException;

final class NowPlayingApiGenerator
{
    public function __construct(
        private readonly SongApiGenerator $songApiGenerator,
        private readonly SongHistoryApiGenerator $songHistoryApiGenerator,
        private readonly StationApiGenerator $stationApiGenerator,
        private readonly StationQueueApiGenerator $stationQueueApiGenerator,
        private readonly Entity\Repository\SongHistoryRepository $historyRepo,
        private readonly Entity\Repository\StationQueueRepository $queueRepo,
        private readonly Entity\Repository\StationStreamerBroadcastRepository $broadcastRepo,
        private readonly Router $router,
    ) {
    }

    public function __invoke(
        Entity\Station $station,
        Result $npResult
    ): Entity\Api\NowPlaying\NowPlaying {
        $baseUri = new Uri('');

        $updateSongFromNowPlaying = !$station->getBackendTypeEnum()->isEnabled();

        if ($updateSongFromNowPlaying && empty($npResult->currentSong->text)) {
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

        try {
            if ($updateSongFromNowPlaying) {
                $this->historyRepo->updateSongFromNowPlaying(
                    $station,
                    Entity\Song::createFromNowPlayingSong($npResult->currentSong)
                );
            }

            $this->historyRepo->updateListenersFromNowPlaying(
                $station,
                $np->listeners->current
            );

            $history = $this->historyRepo->getVisibleHistory(
                $station,
                $station->getApiHistoryItems() + 1
            );

            $currentSong = array_shift($history);

            if (null === $currentSong) {
                throw new RuntimeException('No current song.');
            }
        } catch (Exception $e) {
            Logger::getInstance()->error($e->getMessage(), ['exception' => $e]);

            return $this->offlineApi($station, $baseUri);
        }

        $apiSongHistory = ($this->songHistoryApiGenerator)(
            record: $currentSong,
            baseUri: $baseUri,
            allowRemoteArt: true,
            isNowPlaying: true
        );

        $apiCurrentSong = new Entity\Api\NowPlaying\CurrentSong();
        $apiCurrentSong->fromParentObject($apiSongHistory);
        $np->now_playing = $apiCurrentSong;

        $np->song_history = $this->songHistoryApiGenerator->fromArray(
            $history,
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
                $live->art = $this->router->namedAsUri(
                    routeName: 'api:stations:streamer:art',
                    routeParams: [
                        'station_id' => $station->getIdRequired(),
                        'id' => $currentStreamer->getIdRequired() . '|' . $currentStreamer->getArtUpdatedAt(),
                    ],
                );
            }

            $np->live = $live;
        } else {
            $np->live = new Entity\Api\NowPlaying\Live();
        }

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

    private function offlineApi(
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
}
