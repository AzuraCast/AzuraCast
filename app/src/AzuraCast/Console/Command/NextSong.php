<?php
namespace AzuraCast\Console\Command;

use Entity;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NextSong extends \App\Console\Command\CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:internal:next-song')
            ->setDescription('Return the next song to the AutoDJ.')
            ->addArgument(
                'station_id',
                null,
                'The ID of the station.',
                null
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->di['em'];

        $station_id = (int)$input->getArgument('station_id');
        $station = $em->getRepository(Entity\Station::class)->find($station_id);

        if (!($station instanceof Entity\Station)) {
            return $this->_return($output, 'false');
        }

        // Process requests first (if applicable)
        if ($station->enable_requests) {

            $min_minutes = (int)$station->request_delay;
            $threshold_minutes = $min_minutes + mt_rand(0, $min_minutes);

            $threshold = time() - ($threshold_minutes * 60);

            // Look up all requests that have at least waited as long as the threshold.
            $request = $em->createQuery('SELECT sr, sm 
                FROM Entity\StationRequest sr JOIN sr.track sm
                WHERE sr.played_at = 0 AND sr.station_id = :station_id AND sr.timestamp <= :threshold
                ORDER BY sr.id ASC')
                ->setParameter('station_id', $station->id)
                ->setParameter('threshold', $threshold)
                ->setMaxResults(1)
                ->getOneOrNullResult();

            if ($request instanceof Entity\StationRequest) {
                return $this->_playSongFromRequest($output, $request);
            }

        }

        // Pull all active, non-empty playlists and sort by type.
        $playlists_by_type = [];
        foreach($station->playlists as $playlist) {
            // Don't include empty playlists
            if ($playlist->is_enabled && $playlist->media->count() > 0) {
                $playlists_by_type[$playlist->type][$playlist->id] = $playlist;
            }
        }

        // Pull all recent cued songs for easy referencing below.
        $cued_song_history = $em->createQuery('SELECT sh FROM Entity\SongHistory sh
            WHERE sh.station_id = :station_id
            AND (sh.timestamp_cued != 0 AND sh.timestamp_cued IS NOT NULL)
            AND sh.timestamp_cued >= :threshold
            ORDER BY sh.timestamp_cued DESC')
            ->setParameter('station_id', $station->id)
            ->setParameter('threshold', time()-86399)
            ->getArrayResult();

        // Time-block scheduled playlists
        if (!empty($playlists_by_type['scheduled'])) {
            $current_timecode = $this->_getTimeCode();
            foreach ($playlists_by_type['scheduled'] as $playlist) {
                if ($playlist['schedule_end_time'] < $playlist['schedule_start_time']) {
                    // Overnight playlist
                    $should_be_playing = ($current_timecode >= $playlist['schedule_start_time'] || $current_timecode <= $playlist['schedule_end_time']);
                } else {
                    // Normal playlist
                    $should_be_playing = ($current_timecode >= $playlist['schedule_start_time'] && $current_timecode <= $playlist['schedule_end_time']);
                }

                if ($should_be_playing) {
                    return $this->_playSongFromPlaylist($output, $playlist);
                }
            }
        }

        // Once per day playlists
        if (count($playlists_by_type['once_per_day']) > 0) {
            $current_timecode = $this->_getTimeCode();

            foreach ($playlists_by_type['once_per_day'] as $playlist) {
                $playlist_play_time = $playlist['play_once_time'];
                $playlist_diff = $current_timecode - $playlist_play_time;

                if ($playlist_diff > 0 && $playlist_diff <= 15) {
                    // Check if already played
                    $relevant_song_history = array_slice($cued_song_history, 0, 15);

                    $was_played = false;
                    foreach($relevant_song_history as $sh_row) {
                        if ($sh_row['playlist_id'] == $playlist->id) {
                            $was_played = true;
                            break;
                        }
                    }

                    if (!$was_played) {
                        return $this->_playSongFromPlaylist($output, $playlist);
                    }

                    reset($cued_song_history);
                }
            }
        }

        // Once per X songs playlists
        if (!empty($playlists_by_type['once_per_x_songs'])) {
            foreach($playlists_by_type['once_per_x_songs'] as $playlist) {

                $relevant_song_history = array_slice($cued_song_history, 0, $playlist['play_per_songs']);

                $was_played = false;
                foreach($relevant_song_history as $sh_row) {
                    if ($sh_row['playlist_id'] == $playlist->id) {
                        $was_played = true;
                        break;
                    }
                }

                if (!$was_played) {
                    return $this->_playSongFromPlaylist($output, $playlist);
                }

                reset($cued_song_history);
            }
        }

        // Once per X minutes playlists
        if (!empty($playlists_by_type['once_per_x_minutes'])) {
            foreach($playlists_by_type['once_per_x_minutes'] as $playlist) {
                $threshold = time() - ($playlist['play_per_minutes'] * 60);

                $was_played = false;
                foreach($cued_song_history as $sh_row) {
                    if ($sh_row['timestamp_cued'] < $threshold) {
                        break;
                    } else if ($sh_row['playlist_id'] == $playlist->id) {
                        $was_played = true;
                        break;
                    }
                }

                if (!$was_played) {
                    return $this->_playSongFromPlaylist($output, $playlist);
                }

                reset($cued_song_history);
            }
        }

        // Default rotation playlists
        if (!empty($playlists_by_type['default'])) {
            $playlist_weights = [];
            foreach($playlists_by_type['default'] as $playlist_id => $playlist) {
                $playlist_weights[$playlist_id] = $playlist->weight;
            }

            $rand = mt_rand(1, (int)array_sum($playlist_weights));
            foreach ($playlist_weights as $playlist_id => $weight) {
                $rand -= $weight;
                if ($rand <= 0) {
                    $playlist = $playlists_by_type['default'][$playlist_id];
                    return $this->_playSongFromPlaylist($output, $playlist);
                }
            }
        }

        return $this->_playFallback($output);
    }

    protected function _getTimeCode()
    {
        return (int)gmdate('Gi');
    }

    protected function _playSongFromRequest(OutputInterface $output, Entity\StationRequest $request)
    {
        /** @var EntityManager $em */
        $em = $this->di['em'];

        // Log in history
        $sh = new Entity\SongHistory;
        $sh->song = $request->track->song;
        $sh->station = $request->station;
        $sh->request = $request;

        $sh->timestamp_cued = time();
        $em->persist($sh);

        $request->played_at = time();
        $em->persist($request);

        $em->flush();

        return $this->_playMedia($output, $request->track);
    }

    protected function _playSongFromPlaylist(OutputInterface $output, Entity\StationPlaylist $playlist)
    {
        /** @var EntityManager $em */
        $em = $this->di['em'];

        // Get some random songs from playlist.
        $random_songs = $em->createQuery('SELECT sm, s, st FROM Entity\StationMedia sm
            JOIN sm.song s 
            JOIN sm.station st 
            LEFT JOIN sm.playlists sp
            WHERE sp.id = :playlist_id
            GROUP BY sm.id ORDER BY RAND()')
            ->setParameter('playlist_id', $playlist->id)
            ->setMaxResults(15)
            ->execute();

        // Get all song IDs from the random songs.
        $song_timestamps = [];
        $songs_by_id = [];
        foreach($random_songs as $media_row) {
            $song_timestamps[$media_row->song_id] = 0;
            $songs_by_id[$media_row->song_id] = $media_row;
        }

        // Get the last played timestamps of each song.
        $last_played = $em->createQuery('SELECT sh.song_id AS song_id, MAX(sh.timestamp_start) AS latest_played
            FROM Entity\SongHistory sh
            WHERE  sh.song_id IN (:ids) 
            AND sh.station_id = :station_id 
            AND sh.playlist_id = :playlist_id
            AND sh.timestamp_start != 0
            GROUP BY sh.song_id')
            ->setParameter('ids', array_keys($song_timestamps))
            ->setParameter('station_id', $playlist->station_id)
            ->setParameter('playlist_id', $playlist->id)
            ->getArrayResult();

        // Sort to always play the least recently played song out of the random selection.
        foreach($last_played as $last_played_row) {
            $song_timestamps[$last_played_row['song_id']] = $last_played_row['latest_played'];
        }

        asort($song_timestamps);
        reset($song_timestamps);
        $id_to_play = key($song_timestamps);

        $random_song = $songs_by_id[$id_to_play];

        if ($random_song instanceof Entity\StationMedia) {
            // Log in history
            $sh = new Entity\SongHistory;
            $sh->song = $random_song->song;
            $sh->playlist = $playlist;
            $sh->station = $playlist->station;
            $sh->timestamp_cued = time();

            $em->persist($sh);
            $em->flush();

            return $this->_playMedia($output, $random_song);
        }

        return $this->_playFallback($output);
    }

    protected function _playMedia(OutputInterface $output, Entity\StationMedia $media)
    {
        // Build annotations to send to LiquidSoap
        $annotations = [
            'type="song"',
            'song_id="'.$media->song_id.'"',
        ];

        if ($media->fade_overlap !== null) {
            $annotations[] = 'liq_start_next="'.$media->fade_overlap.'"';
        }
        if ($media->fade_in !== null) {
            $annotations[] = 'liq_fade_in="'.$media->fade_in.'"';
        }
        if ($media->fade_out !== null) {
            $annotations[] = 'liq_fade_out="'.$media->fade_out.'"';
        }

        // 'annotate:type=\"song\",album=\"$ALBUM\",display_desc=\"$FULLSHOWNAME\",liq_start_next=\"2.5\",liq_fade_in=\"3.5\",liq_fade_out=\"3.5\":$SONGPATH'
        $song_path = $media->getFullPath();
        return $this->_return($output, 'annotate:'.implode(',', $annotations).':'.$song_path);
    }

    protected function _playFallback(OutputInterface $output)
    {
        $fallback_song_path = APP_INCLUDE_ROOT.'/resources/error.mp3';
        return $this->_return($output, $fallback_song_path);
    }

    protected function _return(OutputInterface $output, $result)
    {
        $output->write($result);
        return true;
    }
}