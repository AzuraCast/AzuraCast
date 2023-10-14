<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Cache\NowPlayingCache;
use App\Container\LoggerAwareTrait;
use App\Entity\Api\NowPlaying\CurrentSong;
use App\Entity\Api\NowPlaying\Listeners;
use App\Entity\Api\NowPlaying\Live;
use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Repository\SongHistoryRepository;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\Repository\StationStreamerBroadcastRepository;
use App\Entity\Song;
use App\Entity\Station;
use App\Entity\StationQueue;
use App\Http\Router;
use App\Radio\Adapters;
use Exception;
use GuzzleHttp\Psr7\Uri;
use NowPlaying\Result\Result;
use Psr\Http\Message\UriInterface;
use RuntimeException;

final class NowPlayingApiGenerator
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly SongApiGenerator $songApiGenerator,
        private readonly SongHistoryApiGenerator $songHistoryApiGenerator,
        private readonly StationApiGenerator $stationApiGenerator,
        private readonly StationQueueApiGenerator $stationQueueApiGenerator,
        private readonly SongHistoryRepository $historyRepo,
        private readonly StationQueueRepository $queueRepo,
        private readonly StationStreamerBroadcastRepository $broadcastRepo,
        private readonly Adapters $adapters,
        private readonly Router $router,
        private readonly NowPlayingCache $nowPlayingCache
    ) {
    }

    public function __invoke(
        Station $station,
        Result $npResult,
        ?NowPlaying $npOld
    ): NowPlaying {
        $baseUri = new Uri('');

        // Only update songs directly from NP results if we're not getting them fed to us from the backend.
        $updateSongFromNowPlaying = !$station->getBackendType()->isEnabled();

        if ($updateSongFromNowPlaying && empty($npResult->currentSong->text)) {
            return $this->offlineApi($station, $baseUri);
        }

        $np = new NowPlaying();

        if ($updateSongFromNowPlaying) {
            $np->is_online = $npResult->meta->online;
        } else {
            $np->is_online = $this->adapters->getBackendAdapter($station)?->isRunning($station) ?? false;
        }

        $np->station = $this->stationApiGenerator->__invoke($station, $baseUri);
        $np->listeners = new Listeners(
            total: $npResult->listeners->total,
            unique: $npResult->listeners->unique
        );

        try {
            if ($updateSongFromNowPlaying) {
                $this->historyRepo->updateSongFromNowPlaying(
                    $station,
                    Song::createFromNowPlayingSong($npResult->currentSong)
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
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->offlineApi($station, $baseUri);
        }

        $apiSongHistory = $this->songHistoryApiGenerator->__invoke(
            record: $currentSong,
            baseUri: $baseUri,
            allowRemoteArt: true,
            isNowPlaying: true
        );

        $apiCurrentSong = new CurrentSong();
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

            $live = new Live();
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
            $np->live = new Live();
        }

        $np->update();
        return $np;
    }

    public function currentOrEmpty(
        Station $station
    ): NowPlaying {
        $np = $this->nowPlayingCache->getForStation($station);
        return $np ?? $this->offlineApi($station);
    }

    private function offlineApi(
        Station $station,
        ?UriInterface $baseUri = null
    ): NowPlaying {
        $np = new NowPlaying();

        $np->station = $this->stationApiGenerator->__invoke($station, $baseUri);
        $np->listeners = new Listeners();

        $songObj = Song::createOffline($station->getBrandingConfig()->getOfflineText());

        $offlineApiNowPlaying = new CurrentSong();
        $offlineApiNowPlaying->sh_id = 0;
        $offlineApiNowPlaying->song = $this->songApiGenerator->__invoke(
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
        if ($nextVisible instanceof StationQueue) {
            $np->playing_next = ($this->stationQueueApiGenerator)(
                $nextVisible,
                $baseUri,
                true
            );
        }

        $np->live = new Live();

        $np->update();
        return $np;
    }
}
