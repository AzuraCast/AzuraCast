<?php
namespace App\Radio;

use App\Entity;
use Doctrine\ORM\EntityManager;
use App\Cache;

class AutoDJ
{
    /** @var int The time to live (in seconds) of cached playlist queues. */
    const CACHE_TTL = 43200;

    /** @var EntityManager */
    protected $em;

    /** @var Cache */
    protected $cache;

    /**
     * @param EntityManager $em
     * @param Cache $cache
     * @see \App\Provider\RadioProvider
     */
    public function __construct(EntityManager $em, Cache $cache)
    {
        $this->em = $em;
        $this->cache = $cache;
    }

    /**
     * If the next song for a station has already been calculated, return the calculated result; otherwise,
     * calculate the next playing song.
     * 
     * @param Entity\Station $station
     * @param bool $is_autodj
     * @return Entity\SongHistory|null
     */
    public function getNextSong(Entity\Station $station, $is_autodj = false): ?Entity\SongHistory
    {
        if ($station->useManualAutoDJ()) {
            return null;
        }

        $next_song = $this->em->createQuery('SELECT sh, s, sm
            FROM ' . Entity\SongHistory::class . ' sh JOIN sh.song s JOIN sh.media sm
            WHERE sh.station_id = :station_id
            AND sh.timestamp_cued >= :threshold
            AND sh.sent_to_autodj = 0
            AND sh.timestamp_start = 0
            AND sh.timestamp_end = 0
            ORDER BY sh.id DESC')
            ->setParameter('station_id', $station->getId())
            ->setParameter('threshold', time() - 60 * 15)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if (!($next_song instanceof Entity\SongHistory)) {
            $next_song = $this->calculateNextSong($station);
        }

        if ($next_song instanceof Entity\SongHistory && $is_autodj) {
            $next_song->sentToAutodj();
            $this->em->persist($next_song);

            // The "get next song" function is only called when a streamer is not live
            $station->setIsStreamerLive(false);
            $this->em->persist($station);

            $this->em->flush();
        }

        return $next_song;
    }

    /**
     * Determine the next-playing song for this station based on its playlist rotation rules.
     *
     * @param Entity\Station $station
     * @return Entity\SongHistory|null
     */
    public function calculateNextSong(Entity\Station $station)
    {
        // Process requests first (if applicable)
        if ($station->getEnableRequests()) {

            $min_minutes = (int)$station->getRequestDelay();
            $threshold_minutes = $min_minutes + mt_rand(0, $min_minutes);

            $threshold = time() - ($threshold_minutes * 60);

            // Look up all requests that have at least waited as long as the threshold.
            $request = $this->em->createQuery('SELECT sr, sm 
                FROM '.Entity\StationRequest::class.' sr JOIN sr.track sm
                WHERE sr.played_at = 0 AND sr.station_id = :station_id AND sr.timestamp <= :threshold
                ORDER BY sr.id ASC')
                ->setParameter('station_id', $station->getId())
                ->setParameter('threshold', $threshold)
                ->setMaxResults(1)
                ->getOneOrNullResult();

            if ($request instanceof Entity\StationRequest) {
                return $this->_playSongFromRequest($request);
            }

        }

        // Pull all active, non-empty playlists and sort by type.
        $playlists_by_type = [];
        foreach($station->getPlaylists() as $playlist) {
            /** @var Entity\StationPlaylist $playlist */

            // Don't include empty playlists or nonstandard ones
            if ($playlist->getIsEnabled()
                && $playlist->getSource() === Entity\StationPlaylist::SOURCE_SONGS
                && $playlist->getMediaItems()->count() > 0) {
                $playlists_by_type[$playlist->getType()][$playlist->getId()] = $playlist;
            }
        }

        // Pull all recent cued songs for easy referencing below.
        $cued_song_history = $this->em->createQuery('SELECT sh FROM '.Entity\SongHistory::class.' sh
            WHERE sh.station_id = :station_id
            AND (sh.timestamp_cued != 0 AND sh.timestamp_cued IS NOT NULL)
            AND sh.timestamp_cued >= :threshold
            ORDER BY sh.timestamp_cued DESC')
            ->setParameter('station_id', $station->getId())
            ->setParameter('threshold', time()-86399)
            ->getArrayResult();

        // Once per day playlists
        if (!empty($playlists_by_type['once_per_day'])) {
            foreach ($playlists_by_type['once_per_day'] as $playlist) {
                /** @var Entity\StationPlaylist $playlist */
                if ($playlist->canPlayOnce()) {
                    // Check if already played
                    $relevant_song_history = array_slice($cued_song_history, 0, 15);

                    $was_played = false;
                    foreach($relevant_song_history as $sh_row) {
                        if ($sh_row['playlist_id'] == $playlist->getId()) {
                            $was_played = true;
                            break;
                        }
                    }

                    if (!$was_played) {
                        $sh = $this->_playSongFromPlaylist($playlist);
                        if ($sh) {
                            return $sh;
                        }
                    }

                    reset($cued_song_history);
                }
            }
        }

        // Once per X songs playlists
        if (!empty($playlists_by_type['once_per_x_songs'])) {
            foreach($playlists_by_type['once_per_x_songs'] as $playlist) {
                /** @var Entity\StationPlaylist $playlist */

                $relevant_song_history = array_slice($cued_song_history, 0, $playlist->getPlayPerSongs());

                $was_played = false;
                foreach($relevant_song_history as $sh_row) {
                    if ($sh_row['playlist_id'] == $playlist->getId()) {
                        $was_played = true;
                        break;
                    }
                }

                if (!$was_played) {
                    $sh = $this->_playSongFromPlaylist($playlist);
                    if ($sh) {
                        return $sh;
                    }
                }

                reset($cued_song_history);
            }
        }

        // Once per X minutes playlists
        if (!empty($playlists_by_type['once_per_x_minutes'])) {
            foreach($playlists_by_type['once_per_x_minutes'] as $playlist) {
                /** @var Entity\StationPlaylist $playlist */

                $threshold = time() - ($playlist->getPlayPerMinutes() * 60);

                $was_played = false;
                foreach($cued_song_history as $sh_row) {
                    if ($sh_row['timestamp_cued'] < $threshold) {
                        break;
                    } else if ($sh_row['playlist_id'] == $playlist->getId()) {
                        $was_played = true;
                        break;
                    }
                }

                if (!$was_played) {
                    $sh = $this->_playSongFromPlaylist($playlist);
                    if ($sh) {
                        return $sh;
                    }
                }

                reset($cued_song_history);
            }
        }

        // Time-block scheduled playlists
        if (!empty($playlists_by_type['scheduled'])) {
            foreach ($playlists_by_type['scheduled'] as $playlist) {
                /** @var Entity\StationPlaylist $playlist */
                if ($playlist->canPlayScheduled()) {
                    $sh = $this->_playSongFromPlaylist($playlist);
                    if ($sh) {
                        return $sh;
                    }
                }
            }
        }

        // Default rotation playlists
        if (!empty($playlists_by_type['default'])) {
            $playlist_weights = [];
            foreach($playlists_by_type['default'] as $playlist_id => $playlist) {
                /** @var Entity\StationPlaylist $playlist */
                $playlist_weights[$playlist_id] = $playlist->getWeight();
            }

            $rand = random_int(1, (int)array_sum($playlist_weights));
            foreach ($playlist_weights as $playlist_id => $weight) {
                $rand -= $weight;
                if ($rand <= 0) {
                    $playlist = $playlists_by_type['default'][$playlist_id];

                    $sh = $this->_playSongFromPlaylist($playlist);
                    if ($sh) {
                        return $sh;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Mark a playlist's cache as invalidated and force regeneration on the next "next song" call.
     *
     * @param int $playlist_id
     */
    public function clearPlaybackCache($playlist_id): void
    {
        $this->cache->remove($this->_getCacheName($playlist_id));
    }

    /**
     * Given a StationRequest object, create a new SongHistory entry that cues the requested song to play next.
     *
     * @param Entity\StationRequest $request
     * @return Entity\SongHistory
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function _playSongFromRequest(Entity\StationRequest $request)
    {
        // Log in history
        $sh = new Entity\SongHistory($request->getTrack()->getSong(), $request->getStation());
        $sh->setRequest($request);
        $sh->setMedia($request->getTrack());

        $sh->setDuration($request->getTrack()->getCalculatedLength());
        $sh->setTimestampCued(time());
        $this->em->persist($sh);

        $request->setPlayedAt(time());
        $this->em->persist($request);

        $this->em->flush();

        return $sh;
    }

    /**
     * Given a specified (sequential or shuffled) playlist, choose a song from the playlist to play and return it.
     *
     * @param Entity\StationPlaylist $playlist
     * @return Entity\SongHistory|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function _playSongFromPlaylist(Entity\StationPlaylist $playlist)
    {
        $cache_name = $this->_getCacheName($playlist->getId());
        $media_queue = (array)$this->cache->get($cache_name);

        if (empty($media_queue)) {
            $all_media = $this->em->createQuery('SELECT sm.id FROM '.Entity\StationMedia::class.' sm
                JOIN sm.playlist_items spm
                WHERE spm.playlist_id = :playlist_id
                ORDER BY spm.weight ASC')
                    ->setParameter('playlist_id', $playlist->getId())
                    ->getArrayResult();

            $media_queue = [];
            foreach($all_media as $media_row) {
                $media_queue[] = $media_row['id'];
            }

            if ($playlist->getOrder() === Entity\StationPlaylist::ORDER_SHUFFLE) {
                // Build a queue with the song arrangement randomized.
                shuffle($media_queue);
            } else if ($playlist->getOrder() === Entity\StationPlaylist::ORDER_RANDOM) {
                // The queue should always consist of one randomly selected song.
                shuffle($media_queue);
                $media_queue = [array_pop($media_queue)];
            }
        }

        $media_id = array_shift($media_queue);

        // Save the modified cache, sans the now-missing entry.
        $this->cache->set($media_queue, $cache_name, self::CACHE_TTL);

        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $this->em->getRepository(Entity\StationMedia::class);

        $media_to_play = $media_repo->find($media_id);

        if ($media_to_play instanceof Entity\StationMedia) {
            $spm = $media_to_play->getItemForPlaylist($playlist);
            $spm->played();
            $this->em->persist($spm);

            // Log in history
            $sh = new Entity\SongHistory($media_to_play->getSong(), $playlist->getStation());
            $sh->setPlaylist($playlist);
            $sh->setMedia($media_to_play);

            $sh->setDuration($media_to_play->getCalculatedLength());
            $sh->setTimestampCued(time());

            $this->em->persist($sh);
            $this->em->flush();

            return $sh;
        }

        return null;
    }

    /**
     * Get the cache name for the given playlist.
     *
     * @param int $playlist_id
     * @return string
     */
    protected function _getCacheName($playlist_id): string
    {
        return 'autodj/playlist_'.$playlist_id;
    }
}
