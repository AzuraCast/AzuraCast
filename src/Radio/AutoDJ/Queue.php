<?php

namespace App\Radio\AutoDJ;

use App\Entity;
use App\Event\Radio\BuildQueue;
use App\Radio\PlaylistParser;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Queue implements EventSubscriberInterface
{
    protected EntityManagerInterface $em;

    protected LoggerInterface $logger;

    protected Scheduler $scheduler;

    protected Entity\Repository\StationPlaylistMediaRepository $spmRepo;

    protected Entity\Repository\StationRequestRepository $requestRepo;

    protected Entity\Repository\SongHistoryRepository $historyRepo;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Scheduler $scheduler,
        Entity\Repository\StationPlaylistMediaRepository $spmRepo,
        Entity\Repository\StationRequestRepository $requestRepo,
        Entity\Repository\SongHistoryRepository $historyRepo
    ) {
        $this->em = $em;
        $this->logger = $logger;
        $this->scheduler = $scheduler;
        $this->spmRepo = $spmRepo;
        $this->requestRepo = $requestRepo;
        $this->historyRepo = $historyRepo;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            BuildQueue::class => [
                ['getNextSongFromRequests', 5],
                ['calculateNextSong', 0],
            ],
        ];
    }

    /**
     * Determine the next-playing song for this station based on its playlist rotation rules.
     *
     * @param BuildQueue $event
     */
    public function calculateNextSong(BuildQueue $event): void
    {
        $this->logger->info('AzuraCast AutoDJ is calculating the next song to play...');

        $station = $event->getStation();
        $now = $event->getNow();

        $oncePerXSongHistoryCount = 15;

        // Pull all active, non-empty playlists and sort by type.
        $has_any_valid_playlists = false;
        $playlists_by_type = [];
        foreach ($station->getPlaylists() as $playlist) {
            /** @var Entity\StationPlaylist $playlist */
            if ($playlist->isPlayable()) {
                $has_any_valid_playlists = true;
                $type = $playlist->getType();

                if (Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS === $type) {
                    $oncePerXSongHistoryCount = max($oncePerXSongHistoryCount, $playlist->getPlayPerSongs());
                }

                $subType = ($playlist->getScheduleItems()->count() > 0) ? 'scheduled' : 'unscheduled';
                $playlists_by_type[$type . '_' . $subType][$playlist->getId()] = $playlist;
            }
        }

        if (!$has_any_valid_playlists) {
            $this->logger->error('No valid playlists detected. Skipping AutoDJ calculations.');
            return;
        }

        $recentSongHistoryForOncePerXSongs = $this->historyRepo->getRecentlyPlayed(
            $station,
            $now,
            $oncePerXSongHistoryCount
        );

        $recentSongHistoryForDuplicatePrevention = $this->historyRepo->getRecentlyPlayedByTimeRange(
            $station,
            $now,
            $station->getBackendConfig()->getDuplicatePreventionTimeRange()
        );

        $logOncePerXSongsSongHistory = [];
        foreach ($recentSongHistoryForOncePerXSongs as $row) {
            $logOncePerXSongsSongHistory[] = [
                'song' => $row['text'],
                'cued_at' => (string)(CarbonImmutable::createFromTimestamp(
                    $row['timestamp_cued'] ?? $row['timestamp_start'],
                    $now->getTimezone()
                )),
                'duration' => $row['duration'],
                'sent_to_autodj' => $row['sent_to_autodj'],
            ];
        }

        $logDuplicatePreventionSongHistory = [];
        foreach ($recentSongHistoryForDuplicatePrevention as $row) {
            $logDuplicatePreventionSongHistory[] = [
                'song' => $row['text'],
                'cued_at' => (string)(CarbonImmutable::createFromTimestamp(
                    $row['timestamp_cued'] ?? $row['timestamp_start'],
                    $now->getTimezone()
                )),
                'duration' => $row['duration'],
                'sent_to_autodj' => $row['sent_to_autodj'],
            ];
        }

        $this->logger->debug('AutoDJ recent song playback history', [
            'history_once_per_x_songs' => $logOncePerXSongsSongHistory,
            'history_duplicate_prevention' => $logDuplicatePreventionSongHistory,
        ]);

        // Types of playlists that should play, sorted by priority.
        $typesToPlay = [
            Entity\StationPlaylist::TYPE_ONCE_PER_HOUR . '_scheduled',
            Entity\StationPlaylist::TYPE_ONCE_PER_HOUR . '_unscheduled',
            Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS . '_scheduled',
            Entity\StationPlaylist::TYPE_ONCE_PER_X_SONGS . '_unscheduled',
            Entity\StationPlaylist::TYPE_ONCE_PER_X_MINUTES . '_scheduled',
            Entity\StationPlaylist::TYPE_ONCE_PER_X_MINUTES . '_unscheduled',
            Entity\StationPlaylist::TYPE_DEFAULT . '_scheduled',
            Entity\StationPlaylist::TYPE_DEFAULT . '_unscheduled',
        ];

        foreach ($typesToPlay as $type) {
            if (empty($playlists_by_type[$type])) {
                continue;
            }

            $log_playlists = [];
            $eligible_playlists = [];
            foreach ($playlists_by_type[$type] as $playlist_id => $playlist) {
                /** @var Entity\StationPlaylist $playlist */
                if ($this->scheduler->shouldPlaylistPlayNow($playlist, $now, $recentSongHistoryForOncePerXSongs)) {
                    $eligible_playlists[$playlist_id] = $playlist->getWeight();
                    $log_playlists[] = [
                        'id' => $playlist->getId(),
                        'name' => $playlist->getName(),
                        'weight' => $playlist->getWeight(),
                    ];
                }
            }

            if (empty($eligible_playlists)) {
                continue;
            }

            $this->logger->info(sprintf(
                '%d playable playlist(s) of type "%s" found.',
                count($eligible_playlists),
                $type
            ), ['playlists' => $log_playlists]);

            // Shuffle playlists by weight.
            $this->weightedShuffle($eligible_playlists);

            // Loop through the playlists and attempt to play them with no duplicates first,
            // then loop through them again while allowing duplicates.
            foreach ([false, true] as $allowDuplicates) {
                foreach ($eligible_playlists as $playlist_id => $weight) {
                    $playlist = $playlists_by_type[$type][$playlist_id];

                    if (
                        $event->setNextSong($this->playSongFromPlaylist(
                            $playlist,
                            $recentSongHistoryForDuplicatePrevention,
                            $now,
                            $allowDuplicates
                        ))
                    ) {
                        $this->logger->info('Playable track found and registered.', [
                            'next_song' => (string)$event,
                        ]);
                        return;
                    }
                }
            }
        }

        $this->logger->error('No playable tracks were found.');
    }

    /**
     * Apply a weighted shuffle to the given array in the form:
     *  [ key1 => weight1, key2 => weight2 ]
     *
     * Based on: https://gist.github.com/savvot/e684551953a1716208fbda6c4bb2f344
     *
     * @param array $array
     */
    protected function weightedShuffle(array &$array): void
    {
        $arr = $array;

        $max = 1.0 / mt_getrandmax();
        array_walk($arr, function (&$v, $k) use ($max): void {
            $v = (mt_rand() * $max) ** (1.0 / $v);
        });
        arsort($arr);
        array_walk($arr, function (&$v, $k) use ($array): void {
            $v = $array[$k];
        });

        $array = $arr;
    }

    /**
     * Given a specified (sequential or shuffled) playlist, choose a song from the playlist to play and return it.
     *
     * @param Entity\StationPlaylist $playlist
     * @param array $recentSongHistory
     * @param CarbonInterface $now
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     */
    protected function playSongFromPlaylist(
        Entity\StationPlaylist $playlist,
        array $recentSongHistory,
        CarbonInterface $now,
        bool $allowDuplicates = false
    ): ?Entity\StationQueue {
        $media_to_play = $this->getQueuedSong($playlist, $recentSongHistory, $allowDuplicates);

        if ($media_to_play instanceof Entity\StationMedia) {
            $playlist->setPlayedAt($now->getTimestamp());
            $this->em->persist($playlist);

            $spm = $media_to_play->getItemForPlaylist($playlist);
            if ($spm instanceof Entity\StationPlaylistMedia) {
                $spm->played($now->getTimestamp());
                $this->em->persist($spm);
            }

            // Log in history
            $sh = new Entity\StationQueue($playlist->getStation(), $media_to_play);
            $sh->setPlaylist($playlist);
            $sh->setMedia($media_to_play);
            $sh->setTimestampCued($now->getTimestamp());

            $this->em->persist($sh);
            $this->em->flush();

            return $sh;
        }

        if (is_array($media_to_play)) {
            [$media_uri, $media_duration] = $media_to_play;

            $playlist->setPlayedAt($now->getTimestamp());
            $this->em->persist($playlist);

            $sh = new Entity\StationQueue(
                $playlist->getStation(),
                Entity\Song::createFromText('Remote Playlist URL')
            );

            $sh->setPlaylist($playlist);
            $sh->setAutodjCustomUri($media_uri);
            $sh->setDuration($media_duration);
            $sh->setTimestampCued($now->getTimestamp());

            $this->em->persist($sh);
            $this->em->flush();

            return $sh;
        }

        return null;
    }

    /**
     * @param Entity\StationPlaylist $playlist
     * @param array $recentSongHistory
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     *
     * @return Entity\StationMedia|mixed[]|null
     */
    protected function getQueuedSong(
        Entity\StationPlaylist $playlist,
        array $recentSongHistory,
        bool $allowDuplicates = false
    ) {
        if (Entity\StationPlaylist::SOURCE_REMOTE_URL === $playlist->getSource()) {
            return $this->playRemoteUrl($playlist);
        }

        switch ($playlist->getOrder()) {
            case Entity\StationPlaylist::ORDER_RANDOM:
                $mediaQueue = $this->spmRepo->getPlayableMedia($playlist);

                if ($playlist->getAvoidDuplicates()) {
                    $mediaId = $this->preventDuplicates($mediaQueue, $recentSongHistory, $allowDuplicates);
                } else {
                    $mediaId = array_key_first($mediaQueue);
                }
                break;

            case Entity\StationPlaylist::ORDER_SEQUENTIAL:
                $mediaQueue = $playlist->getQueue();

                if (empty($mediaQueue)) {
                    $mediaQueue = $this->spmRepo->getPlayableMedia($playlist);
                }

                $media_arr = array_shift($mediaQueue);
                $mediaId = $media_arr['id'];

                $playlist->setQueue($mediaQueue);
                $this->em->persist($playlist);
                break;

            case Entity\StationPlaylist::ORDER_SHUFFLE:
            default:
                $mediaQueue = $playlist->getQueue();
                if (empty($mediaQueue)) {
                    $mediaQueue = $this->spmRepo->getPlayableMedia($playlist);
                }

                if ($playlist->getAvoidDuplicates()) {
                    if ($allowDuplicates) {
                        $mediaId = $this->preventDuplicates($mediaQueue, $recentSongHistory, false);

                        if (null === $mediaId) {
                            $this->logger->warning(
                                'Duplicate prevention yielded no playable song; resetting song queue.'
                            );

                            // Pull the entire shuffled playlist if a duplicate title can't be avoided.
                            $mediaQueue = $this->spmRepo->getPlayableMedia($playlist);
                            $mediaId = $this->preventDuplicates($mediaQueue, $recentSongHistory, true);
                        }
                    } else {
                        $mediaId = $this->preventDuplicates($mediaQueue, $recentSongHistory, false);
                    }
                } else {
                    $mediaId = array_key_first($mediaQueue);
                }

                if (null !== $mediaId) {
                    unset($mediaQueue[$mediaId]);
                }

                // Save the modified cache, sans the now-missing entry.
                $playlist->setQueue($mediaQueue);
                $this->em->persist($playlist);
                break;
        }

        $this->em->flush();

        if (!$mediaId) {
            $this->logger->warning(sprintf('Playlist "%s" did not return a playable track.', $playlist->getName()), [
                'playlist_id' => $playlist->getId(),
                'playlist_order' => $playlist->getOrder(),
                'allow_duplicates' => $allowDuplicates,
            ]);
            return null;
        }

        return $this->em->find(Entity\StationMedia::class, $mediaId);
    }

    /**
     * @return mixed[]|null
     */
    protected function playRemoteUrl(Entity\StationPlaylist $playlist): ?array
    {
        $remote_type = $playlist->getRemoteType() ?? Entity\StationPlaylist::REMOTE_TYPE_STREAM;

        // Handle a raw stream URL of possibly indeterminate length.
        if (Entity\StationPlaylist::REMOTE_TYPE_STREAM === $remote_type) {
            // Annotate a hard-coded "duration" parameter to avoid infinite play for scheduled playlists.
            $duration = $this->scheduler->getPlaylistScheduleDuration($playlist);
            return [$playlist->getRemoteUrl(), $duration];
        }

        // Handle a remote playlist containing songs or streams.
        $media_queue = $playlist->getQueue();

        if (empty($media_queue)) {
            $playlist_raw = file_get_contents($playlist->getRemoteUrl());
            $media_queue = PlaylistParser::getSongs($playlist_raw);
        }

        if (!empty($media_queue)) {
            $media_id = array_shift($media_queue);
        } else {
            $media_id = null;
        }

        // Save the modified cache, sans the now-missing entry.
        $playlist->setQueue($media_queue);
        $this->em->persist($playlist);
        $this->em->flush();

        return ($media_id)
            ? [$media_id, 0]
            : null;
    }

    /**
     * @param array $eligibleMedia
     * @param array $playedMedia
     * @param bool $allowDuplicates Whether to return a media ID even if duplicates can't be prevented.
     */
    protected function preventDuplicates(
        array $eligibleMedia = [],
        array $playedMedia = [],
        bool $allowDuplicates = false
    ): ?int {
        if (empty($eligibleMedia)) {
            $this->logger->debug('Eligible song queue is empty!');
            return null;
        }

        $latestSongIdsPlayed = [];
        $playedTracks = [];

        foreach ($playedMedia as $history) {
            $playedTracks[] = [
                'artist' => $history['artist'],
                'title' => $history['title'],
            ];

            $songId = $history['song_id'];

            if (!isset($latestSongIdsPlayed[$songId])) {
                $latestSongIdsPlayed[$songId] = $history['timestamp_cued'] ?? $history['timestamp_start'];
            }
        }

        $eligibleTracks = [];

        foreach ($eligibleMedia as $media) {
            $songId = $media['song_id'];
            if (isset($latestSongIdsPlayed[$songId])) {
                continue;
            }

            $eligibleTracks[$media['id']] = [
                'artist' => $media['artist'],
                'title' => $media['title'],
            ];
        }

        $mediaId = self::getDistinctTrack($eligibleTracks, $playedTracks);

        if (null !== $mediaId) {
            $this->logger->info(
                'Found track that avoids duplicate title and artist.',
                ['media_id' => $mediaId]
            );

            return $mediaId;
        }

        if ($allowDuplicates) {
            // If we reach this point, there's no way to avoid a duplicate title.
            $mediaIdsByTimePlayed = [];

            // For each piece of eligible media, get its latest played timestamp.
            foreach ($eligibleMedia as $media) {
                $songId = $media['song_id'];
                $mediaIdsByTimePlayed[$media['id']] = $latestSongIdsPlayed[$songId] ?? 0;
            }

            // Pull the lowest value, which corresponds to the least recently played song.
            asort($mediaIdsByTimePlayed);

            $mediaId = array_key_first($mediaIdsByTimePlayed);
            if (null !== $mediaId) {
                $this->logger->warning(
                    'No way to avoid same title OR same artist; using least recently played song.',
                    ['media_id' => $mediaId]
                );

                return $mediaId;
            }
        }

        return null;
    }

    /**
     * @param BuildQueue $event
     */
    public function getNextSongFromRequests(BuildQueue $event): void
    {
        $now = $event->getNow();

        $request = $this->requestRepo->getNextPlayableRequest($event->getStation(), $now);
        if (null === $request) {
            return;
        }

        $this->logger->debug(sprintf('Queueing next song from request ID %d.', $request->getId()));

        // Log in history
        $sq = new Entity\StationQueue($request->getStation(), $request->getTrack());
        $sq->setRequest($request);
        $sq->setMedia($request->getTrack());

        $sq->setDuration($request->getTrack()->getCalculatedLength());
        $sq->setTimestampCued($now->getTimestamp());
        $this->em->persist($sq);

        $request->setPlayedAt($now->getTimestamp());
        $this->em->persist($request);

        $this->em->flush();

        $event->setNextSong($sq);
    }

    /**
     * Given an array of eligible tracks, return the first ID that doesn't have a duplicate artist/
     *   title with any of the previously played tracks.
     *
     * Both should be in the form of an array, i.e.:
     *  [ 'id' => ['artist' => 'Foo', 'title' => 'Fighters'] ]
     *
     * @param array $eligibleTracks
     * @param array $playedTracks
     *
     * @return int|string|null
     */
    public static function getDistinctTrack(array $eligibleTracks, array $playedTracks)
    {
        $artistSeparators = [
            ', ',
            ' feat ',
            ' feat. ',
            ' & ',
        ];
        $dividerString = chr(7);

        $artists = [];
        $titles = [];

        foreach ($playedTracks as $song) {
            $title = trim($song['title']);
            $titles[$title] = $title;

            $artistParts = explode($dividerString, str_replace($artistSeparators, $dividerString, $song['artist']));
            foreach ($artistParts as $artist) {
                $artist = trim($artist);
                if (!empty($artist)) {
                    $artists[$artist] = $artist;
                }
            }
        }

        $eligibleTracksWithoutSameTitle = [];

        foreach ($eligibleTracks as $trackId => $song) {
            // Avoid all direct title matches.
            $title = trim($song['title']);

            if (isset($titles[$title])) {
                continue;
            }

            // Attempt to avoid an artist match, if possible.
            $artist = trim($song['artist']);

            $artistMatchFound = false;
            if (!empty($artist)) {
                $artistParts = explode($dividerString, str_replace($artistSeparators, $dividerString, $artist));
                foreach ($artistParts as $artist) {
                    $artist = trim($artist);
                    if (empty($artist)) {
                        continue;
                    }

                    if (isset($artists[$artist])) {
                        $artistMatchFound = true;
                        break;
                    }
                }
            }

            if (!$artistMatchFound) {
                return $trackId;
            }

            $eligibleTracksWithoutSameTitle[$trackId] = $song;
        }

        foreach ($eligibleTracksWithoutSameTitle as $trackId => $song) {
            return $trackId;
        }

        return null;
    }
}
