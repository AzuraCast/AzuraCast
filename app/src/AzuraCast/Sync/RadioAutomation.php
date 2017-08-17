<?php
namespace AzuraCast\Sync;

use App\Exception;
use Doctrine\ORM\EntityManager;
use Entity;
use Entity\Station;

class RadioAutomation extends SyncAbstract
{
    const DEFAULT_THRESHOLD_DAYS = 14;

    /**
     * Iterate through all stations and attempt to run automated assignment.
     */
    public function run()
    {
        /** @var EntityManager $em */
        $em = $this->di['em'];

        // Check all stations for automation settings.
        $stations = $em->getRepository(Station::class)->findAll();

        $automation_log = $this->di['em']->getRepository('Entity\Settings')->getSetting('automation_log', []);

        foreach ($stations as $station) {
            try {
                if ($this->runStation($station)) {
                    $automation_log[$station->getId()] = $station->getName() . ': SUCCESS';
                }
            } catch (Exception $e) {
                $automation_log[$station->getId()] = $station->getName() . ': ERROR - ' . $e->getMessage();
            }
        }

        $this->di['em']->getRepository('Entity\Settings')->setSetting('automation_log', $automation_log);
    }

    /**
     * Run automated assignment (if enabled) for a given $station.
     *
     * @param Station $station
     * @param bool $force
     * @return bool
     * @throws Exception
     */
    public function runStation(Station $station, $force = false)
    {
        /** @var EntityManager $em */
        $em = $this->di['em'];

        $settings = (array)$station->getAutomationSettings();

        if (empty($settings)) {
            throw new Exception('Automation has not been configured for this station yet.');
        }

        if (!$settings['is_enabled']) {
            throw new Exception('Automation is not enabled for this station.');
        }

        // Check whether assignment needs to be run.
        $threshold_days = (int)$settings['threshold_days'];
        $threshold = time() - (86400 * $threshold_days);

        if (!$force && $station->getAutomationTimestamp() >= $threshold) {
            return false;
        } // No error, but no need to run assignment.

        $playlists = [];
        $original_playlists = [];

        // Related playlists are already automatically sorted by weight.
        $i = 0;

        foreach ($station->getPlaylists() as $playlist) {
            /** @var Entity\StationPlaylist $playlist */

            if ($playlist->getIsEnabled() &&
                $playlist->getType() == 'default' &&
                $playlist->getIncludeInAutomation() == true
            ) {
                // Clear all related media.
                foreach ($playlist->getMedia() as $media) {
                    $song = $media->getSong();
                    if ($song instanceof Entity\Song) {
                        $original_playlists[$song->getId()][] = $i;
                    }

                    $media->getPlaylists()->removeElement($playlist);
                    $em->persist($media);
                }

                $playlists[$i] = $playlist;

                $i++;
            }
        }

        if (count($playlists) == 0) {
            throw new Exception('No playlists have automation enabled.');
        }

        $em->flush();

        $media_report = $this->generateReport($station, $threshold_days);

        $media_report = array_filter($media_report, function ($media) use ($original_playlists) {
            // Remove songs that are already in non-auto-assigned playlists.
            if (!empty($media['playlists'])) {
                return false;
            }

            // Remove songs that weren't already in auto-assigned playlists.
            if (!isset($original_playlists[$media['song_id']])) {
                return false;
            }

            return true;
        });

        // Place all songs with 0 plays back in their original playlists.
        foreach ($media_report as $song_id => $media) {
            if ($media['num_plays'] == 0 && isset($original_playlists[$song_id])) {
                $media_row = $media['record'];

                foreach ($original_playlists[$song_id] as $playlist_key) {
                    $media_row->getPlaylists()->add($playlists[$playlist_key]);
                }

                $em->persist($media_row);

                unset($media_report[$song_id]);
            }
        }

        $em->flush();

        // Sort songs by ratio descending.
        uasort($media_report, function ($a_media, $b_media) {
            $a = (int)$a_media['ratio'];
            $b = (int)$b_media['ratio'];

            return ($a < $b) ? 1 : (($a > $b) ? -1 : 0);
        });

        // Distribute media across the enabled playlists and assign media to playlist.
        $num_songs = count($media_report);
        $num_playlists = count($playlists);
        $songs_per_playlist = floor($num_songs / $num_playlists);

        $i = 0;

        foreach ($playlists as $playlist) {
            if ($i == 0) {
                $playlist_num_songs = $songs_per_playlist + ($num_songs % $num_playlists);
            } else {
                $playlist_num_songs = $songs_per_playlist;
            }

            $media_in_playlist = array_slice($media_report, $i, $playlist_num_songs);
            foreach ($media_in_playlist as $media) {
                $media_row = $media['record'];
                $media_row->getPlaylists()->add($playlist);

                $em->persist($media_row);
            }

            $i += $playlist_num_songs;
        }

        $station->setAutomationTimestamp(time());
        $em->persist($station);
        $em->flush();

        // Write new PLS playlist configuration.
        $station->getBackendAdapter($this->di)->write();

        return true;
    }

    /**
     * Generate a Performance Report for station $station's songs over the last $threshold_days days.
     *
     * @param Station $station
     * @param int $threshold_days
     * @return array
     */
    public function generateReport(Station $station, $threshold_days = self::DEFAULT_THRESHOLD_DAYS)
    {
        /** @var EntityManager $em */
        $em = $this->di['em'];

        $threshold = strtotime('-' . (int)$threshold_days . ' days');

        // Pull all SongHistory data points.
        $data_points_raw = $em->createQuery('SELECT sh.song_id, sh.timestamp_start, sh.delta_positive, sh.delta_negative, sh.listeners_start 
            FROM Entity\SongHistory sh 
            WHERE sh.station_id = :station_id AND sh.timestamp_end != 0 AND sh.timestamp_start >= :threshold')
            ->setParameter('station_id', $station->getId())
            ->setParameter('threshold', $threshold)
            ->getArrayResult();

        $total_plays = 0;
        $data_points = [];
        $data_points_by_hour = [];

        foreach ($data_points_raw as $row) {
            $total_plays++;

            if (!isset($data_points[$row['song_id']])) {
                $data_points[$row['song_id']] = [];
            }

            $row['hour'] = date('H', $row['timestamp_start']);

            if (!isset($totals_by_hour[$row['hour']])) {
                $data_points_by_hour[$row['hour']] = [];
            }

            $data_points_by_hour[$row['hour']][] = $row;

            $data_points[$row['song_id']][] = $row;
        }

        /*
        // Build hourly data point totals.
        $hourly_distributions = array();

        foreach($data_points_by_hour as $hour_code => $hour_rows)
        {
            $hour_listener_points = array();
            foreach($hour_rows as $row)
                $hour_listener_points[] = $row['listeners_start'];

            $hour_plays = count($hour_rows);
            $hour_listeners = array_sum($hour_listener_points) / $hour_plays;

            // ((#CALC#DELTA-X-HR * 100) / #CALC#AVG-LISTENERS-X-HR) / ( #CALC#SONGS-IN-PERIOD / #VAR#MIN-CALC-PLAYS / 24 )
            $hourly_distributions[$hour_code] = ($hour_listeners) * ($hour_plays / 24);
        }
        */

        // Pull all media and playlists.
        $media_repo = $em->getRepository(Entity\StationMedia::class);

        $media_raw = $em->createQuery('SELECT sm, sp FROM Entity\StationMedia sm LEFT JOIN sm.playlists sp WHERE sm.station_id = :station_id ORDER BY sm.artist ASC, sm.title ASC')
            ->setParameter('station_id', $station->getId())
            ->execute();

        $report = [];

        foreach ($media_raw as $row_obj) {
            $row = $media_repo->toArray($row_obj);

            $media = [
                'song_id' => $row['song_id'],
                'record' => $row_obj,

                'title' => $row['title'],
                'artist' => $row['artist'],
                'length_raw' => $row['length'],
                'length' => $row['length_text'],
                'path' => $row['path'],

                'playlists' => [],
                'data_points' => [],

                'num_plays' => 0,
                'percent_plays' => 0,

                'delta_negative' => 0,
                'delta_positive' => 0,
                'delta_total' => 0,

                'ratio' => 0,
            ];

            if ($row_obj->getPlaylists()->count() > 0) {
                foreach ($row_obj->getPlaylists() as $playlist) {
                    $media['playlists'][] = $playlist->getName();
                }
            }

            if (isset($data_points[$row['song_id']])) {
                $ratio_points = [];

                foreach ($data_points[$row['song_id']] as $data_row) {
                    $media['num_plays']++;

                    $media['delta_positive'] += $data_row['delta_positive'];
                    $media['delta_negative'] -= $data_row['delta_negative'];

                    $delta_total = $data_row['delta_positive'] - $data_row['delta_negative'];

                    $ratio_points[] = ($data_row['listeners_start'] == 0) ? 0 : ($delta_total / $data_row['listeners_start']) * 100;

                    // $hour_dist = $hourly_distributions[$data_row['hour']];
                    // ((#REC#PLAY-DELTA*100)/#REC#PLAY-LISTENS)- #CALC#AVG-HOUR-DELTA<#REC#PLAY-TIME>
                    // $ratio_points[] = (($delta_total * 100) / $data_row['listeners_start']) - $hour_dist;
                }

                $media['delta_total'] = $media['delta_positive'] + $media['delta_negative'];
                $media['percent_plays'] = round(($media['num_plays'] / $total_plays) * 100, 2);

                $media['ratio'] = round(array_sum($ratio_points) / count($ratio_points), 3);
            }

            $report[$row['song_id']] = $media;
        }

        return $report;
    }

}