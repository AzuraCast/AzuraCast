<?php
namespace Modules\Stations\Controllers;

use Entity\Station;
use Entity\StationMedia;
use Entity\StationPlaylist;

class ReportsController extends BaseController
{
    public function performanceAction()
    {
        $report_data = $this->_getPerformanceReport();

        switch(strtolower($this->getParam('format')))
        {
            case 'csv':
                $this->doNotRender();

                $export_csv = [[
                    'Song Title',
                    'Song Artist',
                    'Filename',
                    'Length',
                    'Current Playlist',
                    'Delta Joins',
                    'Delta Losses',
                    'Delta Total',
                    'Play Count',
                    'Play Percentage',
                    'Weighted Ratio',
                ]];

                foreach($report_data as $row)
                {
                    $export_csv[] = [
                        $row['title'],
                        $row['artist'],
                        $row['path'],
                        $row['length'],

                        implode('/', $row['playlists']),
                        $row['delta_positive'],
                        $row['delta_negative'],
                        $row['delta_total'],

                        $row['num_plays'],
                        $row['percent_plays'].'%',
                        $row['ratio'],
                    ];
                }

                $filename = $this->station->getShortName().'_media_'.date('Ymd').'.csv';
                \App\Export::csv($export_csv, TRUE, $filename);
            break;

            case 'json':
                return $this->response->setJsonContent($report_data);
            break;

            case 'html':
            default:
                $this->view->report_data = $report_data;
            break;
        }
    }

    public function distributeAction()
    {
        /* TODO: Finish automatic assignment script */
        $report_data = $this->_getPerformanceReport();
    }

    protected function _getPerformanceReport()
    {
        $threshold = strtotime('-14 days');

        // Pull all SongHistory data points.
        $data_points_raw = $this->em->createQuery('SELECT sh.song_id, COUNT(sh.id) AS num_plays, SUM(sh.delta_positive) AS delta_positive, SUM(sh.delta_negative) AS delta_negative FROM Entity\SongHistory sh WHERE sh.station_id = :station_id AND sh.timestamp_end != 0 AND sh.timestamp_start >= :threshold GROUP BY sh.song_id')
            ->setParameter('station_id', $this->station->id)
            ->setParameter('threshold', $threshold)
            ->getArrayResult();

        $total_plays = 0;
        $data_points = array();
        $data_points_by_hour = array();

        foreach($data_points_raw as $row)
        {
            $total_plays += $row['num_plays'];

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
            $hourly_distributions[$hour_code] = (100 / $hour_listeners) / ($hour_plays / 24);
        }

        // Pull all media and playlists.
        $media_raw = $this->em->createQuery('SELECT sm, sp FROM Entity\StationMedia sm LEFT JOIN sm.playlists sp WHERE sm.station_id = :station_id ORDER BY sm.artist ASC, sm.title ASC')
            ->setParameter('station_id', $this->station->id)
            ->getArrayResult();

        $report = array();

        foreach($media_raw as $row)
        {
            $media = array(
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
                    $media['num_plays'] += $data_row['num_plays'];

                    $media['delta_positive'] += $data_row['delta_positive'];
                    $media['delta_negative'] -= $data_row['delta_negative'];

                    $delta_total = $data_row['delta_positive'] - $data_row['delta_negative'];
                    $hour_dist = $hourly_distributions[$data_row['hour']];

                    // ((#REC#PLAY-DELTA*100)/#REC#PLAY-LISTENS)- #CALC#AVG-HOUR-DELTA<#REC#PLAY-TIME>
                    $ratio_points[] = (($delta_total * 100) / $media['listeners_start']) - $hour_dist;
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