<?php
namespace App\Radio;

use App\Exception;
use App\Utilities;
use Entity\Settings;
use Entity\Station;

class Automation
{
    const DEFAULT_THRESHOLD_DAYS = 14;

    /**
     * Iterate through all stations and attempt to run automated assignment.
     */
    public static function run()
    {
        // Check all stations for automation settings.
        $stations = Station::fetchAll();

        $automation_log = Settings::getSetting('automation_log', array());

        foreach($stations as $station)
        {
            try
            {
                if (self::runStation($station))
                    $automation_log[$station->id] = $station->name.': SUCCESS';
            }
            catch(Exception $e)
            {
                $automation_log[$station->id] = $station->name.': ERROR - '.$e->getMessage();
            }
        }

        Settings::setSetting('automation_log', $automation_log);
    }

    /**
     * Run automated assignment (if enabled) for a given $station.
     *
     * @param Station $station
     * @param bool $force
     * @return bool
     * @throws Exception
     */
    public static function runStation(Station $station, $force = false)
    {
        $di = \Phalcon\Di::getDefault();
        $em = $di->get('em');

        $settings = (array)$station->automation_settings;

        if (empty($settings))
            throw new Exception('Automation has not been configured for this station yet.');

        if (!$settings['is_enabled'])
            throw new Exception('Automation is not enabled for this station.');

        // Check whether assignment needs to be run.
        $threshold_days = (int)$settings['threshold_days'];
        $threshold = time()-(86400 * $threshold_days);

        if (!$force && $station->automation_timestamp >= $threshold)
            return false; // No error, but no need to run assignment.

        $playlists = array();
        $original_playlists = array();

        // Related playlists are already automatically sorted by weight.
        $i = 0;

        foreach($station->playlists as $playlist)
        {
            if ($playlist->include_in_automation)
            {
                // Clear all related media.
                foreach($playlist->media as $media)
                {
                    $original_playlists[$media->id][] = $i;

                    $media->playlists->removeElement($playlist);
                    $em->persist($media);
                }

                $playlists[$i] = $playlist;

                $i++;
            }
        }

        if (count($playlists) == 0)
            throw new Exception('No playlists have automation enabled.');

        $em->flush();

        $media_report = self::generateReport($station, $threshold_days);

        // Remove all songs that were not played or that are in non-auto-assigned playlists.
        $media_report = array_filter($media_report, function($media) {
            return empty($media['playlists']);
        });

        // Place all songs with 0 plays back in their original playlists.
        foreach($media_report as $song_id => $media)
        {
            if ($media['num_plays'] == 0 && isset($original_playlists[$song_id]))
            {
                $media_row = $media['record'];

                foreach($original_playlists[$song_id] as $playlist_key)
                    $media_row->playlists->add($playlists[$playlist_key]);

                $em->persist($media_row);

                unset($media_report[$song_id]);
            }
        }

        $em->flush();

        // Sort songs by ratio descending.
        uasort($media_report, function($a_media, $b_media) {
            $a = (int)$a_media['ratio'];
            $b = (int)$b_media['ratio'];

            return ($a < $b) ? 1 : (($a > $b) ? -1 : 0);
        });
        
        // Distribute media across the enabled playlists and assign media to playlist.
        $num_songs = count($media_report);
        $num_playlists = count($playlists);
        $songs_per_playlist = floor($num_songs / $num_playlists);

        $i = 0;

        foreach($playlists as $playlist)
        {
            if ($i == 0)
                $playlist_num_songs = $songs_per_playlist + ($num_songs % $num_playlists);
            else
                $playlist_num_songs = $songs_per_playlist;

            $media_in_playlist = array_slice($media_report, $i, $playlist_num_songs);
            foreach($media_in_playlist as $media)
            {
                $media_row = $media['record'];
                $media_row->playlists->add($playlist);

                $em->persist($media_row);
            }

            $i += $playlist_num_songs;
        }

        $station->automation_timestamp = time();
        $em->persist($station);

        $em->flush();
        return true;
    }

    /**
     * Generate a Performance Report for station $station's songs over the last $threshold_days days.
     *
     * @param Station $station
     * @param int $threshold_days
     * @return array
     */
    public static function generateReport(Station $station, $threshold_days = self::DEFAULT_THRESHOLD_DAYS)
    {
        $di = \Phalcon\Di::getDefault();
        $em = $di->get('em');

        $threshold = strtotime('-'.(int)$threshold_days.' days');

        // Pull all SongHistory data points.
        $data_points_raw = $em->createQuery('SELECT sh.song_id, sh.timestamp_start, sh.delta_positive, sh.delta_negative, sh.listeners_start FROM Entity\SongHistory sh WHERE sh.station_id = :station_id AND sh.timestamp_end != 0 AND sh.timestamp_start >= :threshold')
            ->setParameter('station_id', $station->id)
            ->setParameter('threshold', $threshold)
            ->getArrayResult();

        $total_plays = 0;
        $data_points = array();
        $data_points_by_hour = array();

        foreach($data_points_raw as $row)
        {
            $total_plays ++;

            if (!isset($data_points[$row['song_id']]))
                $data_points[$row['song_id']] = [];

            $row['hour'] = date('H', $row['timestamp_start']);

            if (!isset($totals_by_hour[$row['hour']]))
                $data_points_by_hour[$row['hour']] = array();

            $data_points_by_hour[$row['hour']][] = $row;

            $data_points[$row['song_id']][] = $row;
        }

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

        // Pull all media and playlists.
        $media_raw = $em->createQuery('SELECT sm, sp FROM Entity\StationMedia sm LEFT JOIN sm.playlists sp WHERE sm.station_id = :station_id ORDER BY sm.artist ASC, sm.title ASC')
            ->setParameter('station_id', $station->id)
            ->execute();

        $report = array();

        foreach($media_raw as $row)
        {
            $media = array(
                'record'    => $row,

                'title'     => $row['title'],
                'artist'    => $row['artist'],
                'length'    => $row['length_text'],
                'path'      => $row['path'],

                'playlists' => array(),
                'data_points' => array(),

                'num_plays' => 0,
                'percent_plays' => 0,

                'delta_negative' => 0,
                'delta_positive' => 0,
                'delta_total' => 0,

                'ratio' => 0,
            );

            if (!empty($row['playlists']))
            {
                foreach($row['playlists'] as $playlist)
                    $media['playlists'][] = $playlist['name'];
            }

            if (isset($data_points[$row['song_id']]))
            {
                $ratio_points = array();

                foreach($data_points[$row['song_id']] as $data_row)
                {
                    $media['num_plays'] ++;

                    $media['delta_positive'] += $data_row['delta_positive'];
                    $media['delta_negative'] -= $data_row['delta_negative'];

                    $delta_total = $data_row['delta_positive'] - 1.5*$data_row['delta_negative'];
                    $hour_dist = $hourly_distributions[$data_row['hour']];

                    // ((#REC#PLAY-DELTA*100)/#REC#PLAY-LISTENS)- #CALC#AVG-HOUR-DELTA<#REC#PLAY-TIME>
                    $ratio_points[] = (($delta_total * 100) / $data_row['listeners_start']) - $hour_dist;
                }

                $media['delta_total'] = $media['delta_positive'] + $media['delta_negative'];
                $media['percent_plays'] = round(($media['num_plays'] / $total_plays)*100, 2);

                $media['ratio'] = round(array_sum($ratio_points) / count($ratio_points), 3);
            }

            $report[$row['song_id']] = $media;
        }

        return $report;
    }

}