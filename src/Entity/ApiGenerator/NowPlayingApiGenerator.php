<?php

namespace App\Entity\ApiGenerator;

use App\Entity;
use GuzzleHttp\Psr7\Uri;
use NowPlaying\Result\Result;
use Psr\Http\Message\UriInterface;

class NowPlayingApiGenerator
{
    protected SongApiGenerator $songApiGenerator;

    protected SongHistoryApiGenerator $songHistoryApiGenerator;

    protected StationApiGenerator $stationApiGenerator;

    protected StationQueueApiGenerator $stationQueueApiGenerator;

    protected Entity\Repository\SongHistoryRepository $historyRepo;

    protected Entity\Repository\StationQueueRepository $queueRepo;

    protected Entity\Repository\StationStreamerBroadcastRepository $broadcastRepo;

    public function __construct(
        SongApiGenerator $songApiGenerator,
        SongHistoryApiGenerator $songHistoryApiGenerator,
        StationApiGenerator $stationApiGenerator,
        StationQueueApiGenerator $stationQueueApiGenerator,
        Entity\Repository\SongHistoryRepository $historyRepo,
        Entity\Repository\StationQueueRepository $queueRepo,
        Entity\Repository\StationStreamerBroadcastRepository $broadcastRepo
    ) {
        $this->songApiGenerator = $songApiGenerator;
        $this->songHistoryApiGenerator = $songHistoryApiGenerator;
        $this->stationApiGenerator = $stationApiGenerator;
        $this->stationQueueApiGenerator = $stationQueueApiGenerator;
        $this->historyRepo = $historyRepo;
        $this->queueRepo = $queueRepo;
        $this->broadcastRepo = $broadcastRepo;
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
        $np->station = ($this->stationApiGenerator)($station, $baseUri);
        $np->listeners = new Entity\Api\NowPlayingListeners([
            'current' => $npResult->listeners->current,
            'unique' => $npResult->listeners->unique,
            'total' => $npResult->listeners->total,
        ]);

        // Pull from current NP data if song details haven't changed .
        if ($npOld instanceof Entity\Api\NowPlaying && $this->tracksMatch($npResult, $npOld)) {
            $previousHistory = $this->historyRepo->getCurrent($station)
                ?? Entity\Song::createFromApiSong($npOld->now_playing->song);

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
                $baseUri
            );

            $nextVisible = $this->queueRepo->getNextVisible($station);
            if ($nextVisible instanceof Entity\StationQueue) {
                $np->playing_next = ($this->stationQueueApiGenerator)($nextVisible, $baseUri);
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

        // Register a new item in song history.
        $apiSongHistory = ($this->songHistoryApiGenerator)($sh_obj, $baseUri);

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
        $npOld = $station->getNowplaying();
        if ($npOld instanceof Entity\Api\NowPlaying) {
            return $npOld;
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

        $songObj = Entity\Song::createFromText('Stream Offline');

        $offlineApiNowPlaying = new Entity\Api\NowPlayingCurrentSong();
        $offlineApiNowPlaying->sh_id = 0;
        $offlineApiNowPlaying->song = ($this->songApiGenerator)($songObj, $station, $baseUri);
        $np->now_playing = $offlineApiNowPlaying;

        $np->song_history = $this->songHistoryApiGenerator->fromArray(
            $this->historyRepo->getVisibleHistory($station),
            $baseUri
        );

        $nextVisible = $this->queueRepo->getNextVisible($station);
        if ($nextVisible instanceof Entity\StationQueue) {
            $np->playing_next = ($this->stationQueueApiGenerator)($nextVisible, $baseUri);
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
        return (0 === strcmp($current_song_hash, $npOld->now_playing->song->id));
    }
}
