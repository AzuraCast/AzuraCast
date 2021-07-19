<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity;
use App\Event\Radio\LoadNowPlaying;
use GuzzleHttp\Psr7\Uri;
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
        protected EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function __invoke(
        Entity\Station $station,
        Result $npResult
    ): Entity\Api\NowPlaying {
        $baseUri = new Uri('');

        if (empty($npResult->currentSong->text)) {
            return $this->offlineApi($station, $baseUri);
        }

        $npOld = $station->getNowplaying();

        $np = new Entity\Api\NowPlaying();
        $np->is_online = $npResult->meta->online;
        $np->station = ($this->stationApiGenerator)($station, $baseUri);
        $np->listeners = new Entity\Api\NowPlayingListeners(
            total: $npResult->listeners->total,
            unique: $npResult->listeners->unique
        );

        // Pull from current NP data if song details haven't changed .
        if ($npOld instanceof Entity\Api\NowPlaying && $this->tracksMatch($npResult, $npOld)) {
            $previousHistory = $this->historyRepo->getCurrent($station);

            if (null === $previousHistory) {
                $previousHistory = ($npOld->now_playing?->song)
                    ? Entity\Song::createFromApiSong($npOld->now_playing->song)
                    : Entity\Song::createOffline();
            }

            $sh_obj = $this->historyRepo->register($previousHistory, $station, $np);

            $np->song_history = $npOld->song_history;
            $np->playing_next = $npOld->playing_next;
        } else {
            // SongHistory registration must ALWAYS come before the history/nextsong calls
            // otherwise they will not have up-to-date database info!
            $sh_obj = $this->historyRepo->register(
                Entity\Song::createFromNowPlayingSong($npResult->currentSong),
                $station,
                $np
            );

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
        }

        // Detect and report live DJ status
        if ($station->getIsStreamerLive()) {
            $current_streamer = $station->getCurrentStreamer();
            $streamer_name = ($current_streamer instanceof Entity\StationStreamer)
                ? $current_streamer->getDisplayName()
                : 'Live DJ';

            $broadcastStart = null;
            $broadcast = $this->broadcastRepo->getLatestBroadcast($station);
            if (null !== $broadcast) {
                $broadcastStart = $broadcast->getTimestampStart();
            }

            $np->live = new Entity\Api\NowPlayingLive(true, $streamer_name, $broadcastStart);
        } else {
            $np->live = new Entity\Api\NowPlayingLive(false);
        }

        $apiSongHistory = ($this->songHistoryApiGenerator)($sh_obj, $baseUri, true);
        $apiCurrentSong = new Entity\Api\NowPlayingCurrentSong();
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
    ): Entity\Api\NowPlaying {
        $event = new LoadNowPlaying();
        $this->eventDispatcher->dispatch($event);

        $np = $event->getForStation($station);
        if (null !== $np) {
            return $np;
        }

        return $this->offlineApi($station, $baseUri);
    }

    protected function offlineApi(
        Entity\Station $station,
        ?UriInterface $baseUri = null
    ): Entity\Api\NowPlaying {
        $np = new Entity\Api\NowPlaying();

        $np->station = ($this->stationApiGenerator)($station, $baseUri);
        $np->listeners = new Entity\Api\NowPlayingListeners();

        $songObj = Entity\Song::createOffline();

        $offlineApiNowPlaying = new Entity\Api\NowPlayingCurrentSong();
        $offlineApiNowPlaying->sh_id = 0;
        $offlineApiNowPlaying->song = ($this->songApiGenerator)(
            $songObj,
            $station,
            $baseUri
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

        $np->live = new Entity\Api\NowPlayingLive(false);

        $np->update();
        return $np;
    }

    protected function tracksMatch(
        Result $npResult,
        Entity\Api\NowPlaying $npOld
    ): bool {
        $current_song_hash = Entity\Song::getSongHash($npResult->currentSong);
        return (0 === strcmp($current_song_hash, $npOld->now_playing?->song?->id ?? ''));
    }
}
