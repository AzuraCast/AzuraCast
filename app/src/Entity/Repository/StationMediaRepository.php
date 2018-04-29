<?php
namespace Entity\Repository;

use Doctrine\ORM\NoResultException;
use Entity;

class StationMediaRepository extends BaseRepository
{
    /**
     * @param Entity\Station $station
     * @return array
     */
    public function getRequestable(Entity\Station $station)
    {
        return $this->_em->createQuery('SELECT sm FROM ' . $this->_entityName . ' sm WHERE sm.station_id = :station_id ORDER BY sm.artist ASC, sm.title ASC')
            ->setParameter('station_id', $station->getId())
            ->getArrayResult();
    }

    /**
     * @param Entity\Station $station
     * @param $artist_name
     * @return array
     */
    public function getByArtist(Entity\Station $station, $artist_name)
    {
        return $this->_em->createQuery('SELECT sm FROM ' . $this->_entityName . ' sm WHERE sm.station_id = :station_id AND sm.artist LIKE :artist ORDER BY sm.title ASC')
            ->setParameter('station_id', $station->getId())
            ->setParameter('artist', $artist_name)
            ->getArrayResult();
    }

    /**
     * @param Entity\Station $station
     * @param $query
     * @return array
     */
    public function search(Entity\Station $station, $query)
    {
        $db = $this->_em->getConnection();
        $table_name = $this->_em->getClassMetadata(__CLASS__)->getTableName();

        $stmt = $db->executeQuery('SELECT sm.* FROM ' . $db->quoteIdentifier($table_name) . ' AS sm WHERE sm.station_id = ? AND CONCAT(sm.title, \' \', sm.artist, \' \', sm.album) LIKE ?',
            [$station->getId(), '%' . addcslashes($query, "%_") . '%']);

        return $stmt->fetchAll();
    }

    /**
     * @param Entity\Station $station
     * @param $path
     * @return Entity\StationMedia
     * @throws \Exception
     */
    public function getOrCreate(Entity\Station $station, $path)
    {
        $short_path = ltrim(str_replace($station->getRadioMediaDir(), '', $path), '/');

        $record = $this->findOneBy(['station_id' => $station->getId(), 'path' => $short_path]);

        if (!($record instanceof Entity\StationMedia)) {
            $record = new Entity\StationMedia($station, $short_path);
        }

        $song_info = $record->loadFromFile();
        if (is_array($song_info)) {
            /** @var SongRepository $song_repo */
            $song_repo = $this->_em->getRepository(Entity\Song::class);
            $record->setSong($song_repo->getOrCreate($song_info));
        }

        return $record;
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
                FROM Entity\StationRequest sr JOIN sr.track sm
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
            // Don't include empty playlists
            if ($playlist->getIsEnabled()) {
                if ($playlist->getSource() !== Entity\StationPlaylist::SOURCE_REMOTE_URL
                    || $playlist->getMediaItems()->count() > 0) {
                    $playlists_by_type[$playlist->getType()][$playlist->getId()] = $playlist;
                }
            }
        }

        // Pull all recent cued songs for easy referencing below.
        $cued_song_history = $this->_em->createQuery('SELECT sh FROM Entity\SongHistory sh
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
        if ($playlist->getSource() === Entity\StationPlaylist::SOURCE_SONGS) {
            if ($playlist->getOrder() === Entity\StationPlaylist::ORDER_SEQUENTIAL) {
                $media_to_play = $this->_playSequentialSongFromPlaylist($playlist);
            } else {
                $media_to_play = $this->_playRandomSongFromPlaylist($playlist);
            }

            if ($media_to_play instanceof Entity\StationMedia) {
                /** @var Entity\StationPlaylistMedia $spm */
                $spm = $media_to_play->getPlaylistItems()->first();
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
        } else {
            return $playlist->getRemoteUrl();
        }

        return null;
    }

    protected function _playRandomSongFromPlaylist(Entity\StationPlaylist $playlist)
    {
        // Get some random songs from playlist.
        $random_songs = $this->_em->createQuery('SELECT sm, spm, s, st FROM Entity\StationMedia sm
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
                /** @var Entity\StationPlaylistMedia $playlist_item */
                $playlist_item = $media_row->getPlaylistItems()->first();

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
            $last_played_media = $this->_em->createQuery('SELECT spm FROM Entity\StationPlaylistMedia spm
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
        $next_song_query = $this->_em->createQuery('SELECT spm, sm, s, st FROM Entity\StationPlaylistMedia spm
            JOIN spm.media sm
            JOIN sm.song s 
            JOIN sm.station st
            WHERE spm.weight >= :weight
            ORDER BY spm.weight ASC')
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

    /**
     * Retrieve a key-value representation of all custom metadata for the specified media.
     *
     * @param Entity\StationMedia $media
     * @return array
     */
    public function getCustomFields(Entity\StationMedia $media)
    {
        $metadata_raw = $this->_em->createQuery('SELECT e FROM Entity\StationMediaCustomField e WHERE e.media_id = :media_id')
            ->setParameter('media_id', $media->getId())
            ->getArrayResult();

        $result = [];
        foreach($metadata_raw as $row) {
            $result[$row['field_id']] = $row['value'];
        }

        return $result;
    }

    /**
     * Set the custom metadata for a specified station based on a provided key-value array.
     *
     * @param Entity\StationMedia $media
     * @param array $custom_fields
     */
    public function setCustomFields(Entity\StationMedia $media, array $custom_fields)
    {
        $this->_em->createQuery('DELETE FROM Entity\StationMediaCustomField e WHERE e.media_id = :media_id')
            ->setParameter('media_id', $media->getId())
            ->execute();

        foreach ($custom_fields as $field_id => $field_value) {
            /** @var Entity\CustomField $field */
            $field = $this->_em->getReference(Entity\CustomField::class, $field_id);

            $record = new Entity\StationMediaCustomField($media, $field);
            $record->setValue($field_value);
            $this->_em->persist($record);
        }

        $this->_em->flush();
    }
}