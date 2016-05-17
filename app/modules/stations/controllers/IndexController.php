<?php
namespace Modules\Stations\Controllers;

use \Entity\Station;

use \Entity\Song;
use \Entity\SongHistory;
use \Entity\SongVote;

class IndexController extends BaseController
{
    public function indexAction()
    {
        /**
         * Statistics
         */

        $threshold = strtotime('-1 month');

        // Statistics by day.
        $influx = $this->di->get('influx');
        $influx->setDatabase('stations');

        try
        {
            $daily_stats = $influx->query('SELECT * FROM 1d.station.'.$this->station->id.'.listeners WHERE time > now() - 30d', 'm');
            $daily_stats = array_pop($daily_stats);
        }
        catch(\Exception $e)
        {
            $daily_stats = array();
        }

        $daily_ranges = array();
        $daily_averages = array();
        $days_of_week = array();

        foreach($daily_stats as $stat)
        {
            $daily_ranges[] = array($stat['time'], $stat['min'], $stat['max']);
            $daily_averages[] = array($stat['time'], $stat['value']);

            $day_of_week = date('l', round($stat['time']/1000));
            $days_of_week[$day_of_week][] = $stat['value'];
        }

        $day_of_week_stats = array();
        foreach($days_of_week as $day_name => $day_totals)
            $day_of_week_stats[] = array($day_name, round(array_sum($day_totals) / count($day_totals), 2));

        $this->view->day_of_week_stats = json_encode($day_of_week_stats);

        $this->view->daily_ranges = json_encode($daily_ranges);
        $this->view->daily_averages = json_encode($daily_averages);

        // Statistics by hour.
        try
        {
            $hourly_stats = $influx->query('SELECT * FROM 1h.station.'.$this->station->id.'.listeners', 'm');
            $hourly_stats = array_pop($hourly_stats);
        }
        catch(\Exception $e)
        {
            $hourly_stats = array();
        }

        $hourly_averages = array();
        $hourly_ranges = array();
        $totals_by_hour = array();

        foreach($hourly_stats as $stat)
        {
            $hourly_ranges[] = array($stat['time'], $stat['min'], $stat['max']);
            $hourly_averages[] = array($stat['time'], $stat['value']);

            $hour = date('G', round($stat['time']/1000));
            $totals_by_hour[$hour][] = $stat['value'];
        }

        $this->view->hourly_ranges = json_encode($hourly_ranges);
        $this->view->hourly_averages = json_encode($hourly_averages);

        $averages_by_hour = array();
        for($i = 0; $i < 24; $i++)
        {
            $totals = $totals_by_hour[$i];
            $averages_by_hour[] = array($i.':00', round(array_sum($totals) / count($totals), 2));
        }

        $this->view->averages_by_hour = json_encode($averages_by_hour);

        /**
         * Play Count Statistics
         */

        $song_totals_raw = array();
        $song_totals_raw['played'] = $this->em->createQuery('SELECT sh.song_id, COUNT(sh.id) AS records
            FROM Entity\SongHistory sh
            WHERE sh.station_id = :station_id AND sh.timestamp_start >= :timestamp
            GROUP BY sh.song_id
            ORDER BY records DESC')
            ->setParameter('station_id', $this->station->id)
            ->setParameter('timestamp', $threshold)
            ->setMaxResults(40)
            ->getArrayResult();

        $ignored_songs = $this->_getIgnoredSongs();
        $song_totals_raw['played'] = array_filter($song_totals_raw['played'], function($value) use ($ignored_songs)
        {
            return !(isset($ignored_songs[$value['song_id']]));
        });

        // Compile the above data.
        $song_totals = array();
        foreach($song_totals_raw as $total_type => $total_records)
        {
            foreach($total_records as $total_record)
            {
                $song = \Entity\Song::find($total_record['song_id']);
                $total_record['song'] = $song;

                $song_totals[$total_type][] = $total_record;
            }

            $song_totals[$total_type] = array_slice($song_totals[$total_type], 0, 10, TRUE);
        }

        $this->view->song_totals = $song_totals;

        /**
         * Song "Deltas" (Changes in Listener Count)
         */

        $songs_played_raw = $this->_getEligibleHistory();
        $songs = array();

        foreach($songs_played_raw as $i => $song_row)
        {
            // Song has no recorded ending.
            if ($song_row['timestamp_end'] == 0)
                continue;

            $song_row['stat_start'] = $song_row['listeners_start'];
            $song_row['stat_end'] = $song_row['listeners_end'];
            $song_row['stat_delta'] = $song_row['delta_total'];

            $songs[] = $song_row;
        }

        usort($songs, function($a_arr, $b_arr) {
            $a = $a_arr['stat_delta'];
            $b = $b_arr['stat_delta'];

            if ($a == $b) return 0;
            return ($a > $b) ? 1 : -1;
        });

        $this->view->best_performing_songs = array_reverse(array_slice($songs, -5));
        $this->view->worst_performing_songs = array_slice($songs, 0, 5);
    }

    public function timelineAction()
    {
        $songs_played_raw = $this->_getEligibleHistory();

        // Get current events within threshold.
        $threshold = $songs_played_raw[0]['timestamp'];

        $songs = array();
        foreach ($songs_played_raw as $i => $song_row)
        {
            // Song has no recorded ending.
            if ($song_row['timestamp_end'] == 0)
                continue;

            $song_row['stat_start'] = $song_row['listeners_start'];
            $song_row['stat_end'] = $song_row['listeners_end'];
            $song_row['stat_delta'] = $song_row['delta_total'];

            $songs[] = $song_row;
        }

        $format = $this->getParam('format', 'html');
        if ($format == 'csv')
        {
            $this->doNotRender();

            $export_all = array();
            $export_all[] = array('Date', 'Time', 'Listeners', 'Delta', 'Likes', 'Dislikes', 'Track', 'Artist', 'Event');

            foreach ($songs as $song_row)
            {
                $export_row = array(
                    date('Y-m-d', $song_row['timestamp_start']),
                    date('g:ia', $song_row['timestamp_start']),
                    $song_row['stat_start'],
                    $song_row['stat_delta'],
                    $song_row['score_likes'],
                    $song_row['score_dislikes'],
                    ($song_row['song']['title']) ? $song_row['song']['title'] : $song_row['song']['text'],
                    $song_row['song']['artist'],
                    ($song_row['event']) ? $song_row['event']['title'] : '',
                );

                $export_all[] = $export_row;
            }

            \App\Export::csv($export_all, true, $this->station->getShortName() . '_timeline_' . date('Ymd'));
            return;
        }
        else
        {
            $songs = array_reverse($songs);
            $pager = new \App\Paginator($songs, $this->getParam('page', 1), 50);

            $this->view->pager = $pager;
        }
    }

    /**
     * Utility Functions
     */

    protected function _getEligibleHistory()
    {
        $cache = $this->di->get('cache');
        $cache_name = 'station_center_history_'.$this->station->id;

        $songs_played_raw = $cache->get($cache_name);

        if (!$songs_played_raw)
        {
            try
            {
                $first_song = $this->em->createQuery('SELECT sh.timestamp_start FROM Entity\SongHistory sh
                    WHERE sh.station_id = :station_id AND sh.listeners_start IS NOT NULL
                    ORDER BY sh.timestamp_start ASC')
                    ->setParameter('station_id', $this->station->id)
                    ->setMaxResults(1)
                    ->getSingleScalarResult();
            }
            catch(\Exception $e)
            {
                $first_song = strtotime('Yesterday 00:00:00');
            }

            $min_threshold = strtotime('-2 weeks');
            $threshold = max($first_song, $min_threshold);

            // Get all songs played in timeline.
            $songs_played_raw = $this->em->createQuery('SELECT sh, s
                FROM Entity\SongHistory sh
                LEFT JOIN sh.song s
                WHERE sh.station_id = :station_id AND sh.timestamp_start >= :timestamp AND sh.listeners_start IS NOT NULL
                ORDER BY sh.timestamp_start ASC')
                ->setParameter('station_id', $this->station->id)
                ->setParameter('timestamp', $threshold)
                ->getArrayResult();

            $ignored_songs = $this->_getIgnoredSongs();
            $songs_played_raw = array_filter($songs_played_raw, function($value) use ($ignored_songs)
            {
                return !(isset($ignored_songs[$value['song_id']]));
            });

            $songs_played_raw = array_values($songs_played_raw);

            $cache->save($songs_played_raw, $cache_name, array(), 60*5);
        }

        return $songs_played_raw;
    }

    protected function _getIgnoredSongs()
    {
        $cache = $this->di->get('cache');
        $song_hashes = $cache->get('station_center_ignored_songs');

        if (!$song_hashes)
        {
            $ignored_phrases = array('Offline', 'Sweeper', 'Bumper', 'Unknown');

            $qb = $this->em->createQueryBuilder();
            $qb->select('s.id')->from('Entity\Song', 's');

            foreach($ignored_phrases as $i => $phrase)
            {
                $qb->orWhere('s.text LIKE ?'.($i+1));
                $qb->setParameter($i+1, '%'.$phrase.'%');
            }

            $song_hashes_raw = $qb->getQuery()->getArrayResult();
            $song_hashes = array();

            foreach($song_hashes_raw as $row)
                $song_hashes[$row['id']] = $row['id'];

            $cache->save($song_hashes, 'station_center_ignored_songs', array(), 86400);
        }

        return $song_hashes;
    }
}