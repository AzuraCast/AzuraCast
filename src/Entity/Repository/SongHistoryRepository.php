<?php
namespace App\Entity\Repository;

use Doctrine\ORM\NoResultException;
use App\Entity;

class SongHistoryRepository extends BaseRepository
{
    public function getNextSongForStation(Entity\Station $station, $is_autodj = false)
    {
        if ($station->useManualAutoDJ()) {
            return null;
        }

        $next_song = $this->_em->createQuery('SELECT sh, s, sm
            FROM ' . $this->_entityName . ' sh JOIN sh.song s JOIN sh.media sm
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
            $next_song = $this->getNextSong($station);
        }

        if ($next_song instanceof Entity\SongHistory && $is_autodj) {
            $next_song->sentToAutodj();
            $this->_em->persist($next_song);

            // The "get next song" function is only called when a streamer is not live
            $station->setIsStreamerLive(false);
            $this->_em->persist($station);

            $this->_em->flush();
        }

        return $next_song;
    }

    /**
     * @param Entity\Station $station
     * @param \App\ApiUtilities $api_utils
     * @param int $num_entries
     * @return array
     */
    public function getHistoryForStation(Entity\Station $station, \App\ApiUtilities $api_utils, $num_entries = 5)
    {
        $history = $this->_em->createQuery('SELECT sh, s 
            FROM ' . $this->_entityName . ' sh JOIN sh.song s LEFT JOIN sh.media sm  
            WHERE sh.station_id = :station_id 
            AND sh.timestamp_end != 0
            ORDER BY sh.id DESC')
            ->setParameter('station_id', $station->getId())
            ->setMaxResults($num_entries)
            ->execute();

        $return = [];
        foreach ($history as $sh) {
            /** @var Entity\SongHistory $sh */
            $return[] = $sh->api($api_utils);
        }

        return $return;
    }

    /**
     * @param Entity\Song $song
     * @param Entity\Station $station
     * @param $np
     * @return Entity\SongHistory
     */
    public function register(Entity\Song $song, Entity\Station $station, $np): Entity\SongHistory
    {
        // Pull the most recent history item for this station.
        $last_sh = $this->_em->createQuery('SELECT sh FROM '.Entity\SongHistory::class.' sh
            WHERE sh.station_id = :station_id
            ORDER BY sh.timestamp_start DESC')
            ->setParameter('station_id', $station->getId())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        $listeners = (int)$np['listeners']['current'];

        if ($last_sh instanceof Entity\SongHistory && $last_sh->getSong() === $song) {
            // Updating the existing SongHistory item with a new data point.
            $last_sh->addDeltaPoint($listeners);

            $this->_em->persist($last_sh);
            $this->_em->flush();

            return $last_sh;
        } else {
            // Wrapping up processing on the previous SongHistory item (if present).
            if ($last_sh instanceof Entity\SongHistory) {
                $last_sh->setTimestampEnd(time());
                $last_sh->setListenersEnd($listeners);

                // Calculate "delta" data for previous item, based on all data points.
                $last_sh->addDeltaPoint($listeners);

                $delta_points = (array)$last_sh->getDeltaPoints();

                $delta_positive = 0;
                $delta_negative = 0;
                $delta_total = 0;

                for ($i = 1; $i < count($delta_points); $i++) {
                    $current_delta = $delta_points[$i];
                    $previous_delta = $delta_points[$i - 1];

                    $delta_delta = $current_delta - $previous_delta;
                    $delta_total += $delta_delta;

                    if ($delta_delta > 0) {
                        $delta_positive += $delta_delta;
                    } elseif ($delta_delta < 0) {
                        $delta_negative += abs($delta_delta);
                    }
                }

                $last_sh->setDeltaPositive($delta_positive);
                $last_sh->setDeltaNegative($delta_negative);
                $last_sh->setDeltaTotal($delta_total);

                /** @var ListenerRepository $listener_repo */
                $listener_repo = $this->_em->getRepository(Entity\Listener::class);
                $last_sh->setUniqueListeners($listener_repo->getUniqueListeners($station, $last_sh->getTimestampStart(), time()));

                $this->_em->persist($last_sh);
            }

            // Look for an already cued but unplayed song.
            $sh = $this->_em->createQuery('SELECT sh FROM '.Entity\SongHistory::class.' sh
                WHERE sh.station_id = :station_id
                AND sh.song_id = :song_id
                AND sh.timestamp_cued != 0
                AND sh.timestamp_start = 0
                ORDER BY sh.timestamp_cued DESC')
                ->setParameter('station_id', $station->getId())
                ->setParameter('song_id', $song->getId())
                ->setMaxResults(1)
                ->getOneOrNullResult();

            // Processing a new SongHistory item.
            if (!($sh instanceof Entity\SongHistory))
            {
                $sh = new Entity\SongHistory($song, $station);
            }

            $sh->setTimestampStart(time());
            $sh->setListenersStart($listeners);
            $sh->addDeltaPoint($listeners);

            $this->_em->persist($sh);
            $this->_em->flush();

            return $sh;
        }
    }

    /**
     * Determine the next-playing song for this station based on its playlist rotation rules.
     *
     * @param Entity\Station $station
     * @return Entity\SongHistory|null
     */
    public function getNextSong(Entity\Station $station)
    {
        // Process requests first (if applicable)
        if ($station->getEnableRequests()) {

            $min_minutes = (int)$station->getRequestDelay();
            $threshold_minutes = $min_minutes + mt_rand(0, $min_minutes);

            $threshold = time() - ($threshold_minutes * 60);

            // Look up all requests that have at least waited as long as the threshold.
            $request = $this->_em->createQuery('SELECT sr, sm 
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
        $cued_song_history = $this->_em->createQuery('SELECT sh FROM '.$this->_entityName.' sh
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

    protected function _playSongFromRequest(Entity\StationRequest $request)
    {
        // Log in history
        $sh = new Entity\SongHistory($request->getTrack()->getSong(), $request->getStation());
        $sh->setRequest($request);
        $sh->setMedia($request->getTrack());

        $sh->setDuration($request->getTrack()->getCalculatedLength());
        $sh->setTimestampCued(time());
        $this->_em->persist($sh);

        $request->setPlayedAt(time());
        $this->_em->persist($request);

        $this->_em->flush();

        return $sh;
    }

    protected function _playSongFromPlaylist(Entity\StationPlaylist $playlist)
    {
        if ($playlist->getOrder() === Entity\StationPlaylist::ORDER_SEQUENTIAL) {
            $media_to_play = $this->_playSequentialSongFromPlaylist($playlist);
        } else {
            $media_to_play = $this->_playRandomSongFromPlaylist($playlist);
        }

        if ($media_to_play instanceof Entity\StationMedia) {
            $spm = $media_to_play->getItemForPlaylist($playlist);
            $spm->played();
            $this->_em->persist($spm);

            // Log in history
            $sh = new Entity\SongHistory($media_to_play->getSong(), $playlist->getStation());
            $sh->setPlaylist($playlist);
            $sh->setMedia($media_to_play);

            $sh->setDuration($media_to_play->getCalculatedLength());
            $sh->setTimestampCued(time());

            $this->_em->persist($sh);
            $this->_em->flush();

            return $sh;
        }

        return null;
    }

    protected function _playRandomSongFromPlaylist(Entity\StationPlaylist $playlist)
    {
        // Get some random songs from playlist.
        $random_songs = $this->_em->createQuery('SELECT sm, spm, s, st FROM '.Entity\StationMedia::class.' sm
            JOIN sm.song s 
            JOIN sm.station st 
            JOIN sm.playlist_items spm
            JOIN spm.playlist sp
            WHERE spm.playlist_id = :playlist_id
            GROUP BY sm.id ORDER BY RAND()')
            ->setParameter('playlist_id', $playlist->getId())
            ->setMaxResults(15)
            ->execute();

        /** @var bool Whether to use "last song ID played" or "genuine random shuffle" mode. */
        $use_song_ids = true;

        // Get all song IDs from the random songs.
        $song_timestamps = [];
        $songs_by_id = [];
        foreach($random_songs as $media_row) {
            /** @var Entity\StationMedia $media_row */

            if ($media_row->getLength() == 0) {
                $use_song_ids = false;
                break;
            } else {
                $playlist_item = $media_row->getItemForPlaylist($playlist);

                $song_timestamps[$media_row->getSong()->getId()] = $playlist_item->getLastPlayed();
                $songs_by_id[$media_row->getSong()->getId()] = $media_row;
            }
        }

        if ($use_song_ids) {
            asort($song_timestamps);
            reset($song_timestamps);
            $id_to_play = key($song_timestamps);

            $random_song = $songs_by_id[$id_to_play];
        } else {
            shuffle($random_songs);
            $random_song = array_pop($random_songs);
        }

        return $random_song;
    }

    protected function _playSequentialSongFromPlaylist(Entity\StationPlaylist $playlist)
    {
        // Fetch the most recently played song
        try {
            /** @var Entity\StationPlaylistMedia $last_played_media */
            $last_played_media = $this->_em->createQuery('SELECT spm FROM '.Entity\StationPlaylistMedia::class.' spm
            WHERE spm.playlist_id = :playlist_id
            ORDER BY spm.last_played DESC')
                ->setParameter('playlist_id', $playlist->getId())
                ->setMaxResults(1)
                ->getSingleResult();
        } catch(NoResultException $e) {
            return null;
        }

        $last_weight = (int)$last_played_media->getWeight();

        // Try to find a song of greater weight. If none exists, start back with zero.
        $next_song_query = $this->_em->createQuery('SELECT spm, sm, s, st FROM '.Entity\StationPlaylistMedia::class.' spm
            JOIN spm.media sm
            JOIN sm.song s 
            JOIN sm.station st
            WHERE spm.playlist_id = :playlist_id 
            AND spm.weight >= :weight
            ORDER BY spm.weight ASC')
            ->setParameter('playlist_id', $playlist->getId())
            ->setMaxResults(1);

        try {
            $next_song = $next_song_query
                ->setParameter('weight', $last_weight+1)
                ->getSingleResult();
        } catch(NoResultException $e) {
            $next_song = $next_song_query
                ->setParameter('weight', 0)
                ->getSingleResult();
        }

        /** @var Entity\StationPlaylistMedia $next_song */
        return $next_song->getMedia();
    }
}
