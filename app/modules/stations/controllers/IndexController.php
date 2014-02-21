<?php
use \Entity\Station;
use \Entity\StationManager;

class Stations_IndexController extends \PVL\Controller\Action\Station
{
    public function selectAction()
    {}

	public function indexAction()
    {
        /**
         * Statistics
         */

        $threshold = strtotime('-1 month');

        // Statistics by day.
        $daily_stats = $this->em->createQuery('SELECT a FROM Entity\Analytics a WHERE a.station_id = :station_id AND a.type = :type ORDER BY a.timestamp ASC')
            ->setParameter('station_id', $this->station->id)
            ->setParameter('type', 'day')
            ->getArrayResult();

        $daily_ranges = array();
        $daily_averages = array();
        $days_of_week = array();

        foreach($daily_stats as $stat)
        {
            $daily_ranges[] = array($stat['timestamp']*1000, $stat['number_min'], $stat['number_max']);
            $daily_averages[] = array($stat['timestamp']*1000, $stat['number_avg']);

            $day_of_week = date('l', $stat['timestamp']+(86400/2));
            $days_of_week[$day_of_week][] = $stat['number_avg'];
        }

        $day_of_week_stats = array();
        foreach($days_of_week as $day_name => $day_totals)
            $day_of_week_stats[] = array($day_name, round(array_sum($day_totals) / count($day_totals), 2));

        $this->view->day_of_week_stats = json_encode($day_of_week_stats);

        $this->view->daily_ranges = json_encode($daily_ranges);
        $this->view->daily_averages = json_encode($daily_averages);

        // Statistics by hour.
        $hourly_stats = $this->em->createQuery('SELECT a FROM Entity\Analytics a WHERE a.station_id = :station_id AND a.type = :type AND a.timestamp >= :timestamp ORDER BY a.timestamp ASC')
            ->setParameter('station_id', $this->station->id)
            ->setParameter('type', 'hour')
            ->setParameter('timestamp', $threshold)
            ->getArrayResult();

        $hourly_averages = array();
        $hourly_ranges = array();
        $totals_by_hour = array();

        foreach($hourly_stats as $stat)
        {
            $hourly_ranges[] = array($stat['timestamp']*1000, $stat['number_min'], $stat['number_max']);
            $hourly_averages[] = array($stat['timestamp']*1000, $stat['number_avg']);

            $hour = date('G', $stat['timestamp']);
            $totals_by_hour[$hour][] = $stat['number_avg'];
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

        // Song statistics.
        $song_totals = array();
        
        // Most played songs.
        $song_totals_raw = array();
        $song_totals_raw['played'] = $this->em->createQuery('SELECT sh.song_id, COUNT(sh.id) AS records FROM Entity\SongHistory sh WHERE sh.station_id = :station_id AND sh.timestamp >= :timestamp GROUP BY sh.song_id ORDER BY records DESC')
            ->setParameter('station_id', $this->station->id)
            ->setParameter('timestamp', $threshold)
            ->setMaxResults(40)
            ->getArrayResult();

        // Compile the above data.
        $song_totals = array();
        foreach($song_totals_raw as $total_type => $total_records)
        {
            foreach($total_records as $total_record)
            {
                $song = \Entity\Song::find($total_record['song_id']);
                $total_record['song'] = $song;

                if ($this->_ignoreSong($song['text']))
                    continue;

                $song_totals[$total_type][] = $total_record;
            }

            $song_totals[$total_type] = array_slice($song_totals[$total_type], 0, 10, TRUE);
        }

        $this->view->song_totals = $song_totals;

        /**
         * Song "Deltas" (Changes in Listener Count)
         */

        $threshold = strtotime('yesterday 00:00:00');

        $stats_raw = $this->em->createQuery('SELECT s FROM Entity\Statistic s WHERE s.timestamp >= :datetime')
            ->setParameter('datetime', date('Y-m-d G:i:s', $threshold))
            ->getArrayResult();

        $stats = array();
        $short_name = $this->station->short_name;

        foreach($stats_raw as $stat_row)
        {
            $stat_timestamp = $stat_row['timestamp']->getTimestamp();
            $stat_for_station = (int)$stat_row['total_stations'][$short_name];

            $stats[$stat_timestamp] = $stat_for_station;
        }

        $songs_played_raw = $this->em->createQuery('SELECT sh, s FROM Entity\SongHistory sh JOIN sh.song s WHERE sh.station_id = :station_id AND sh.timestamp >= :timestamp ORDER BY sh.timestamp ASC')
            ->setParameter('station_id', $this->station->id)
            ->setParameter('timestamp', $threshold)
            ->getArrayResult();

        $songs = array();

        foreach($songs_played_raw as $i => $song_row)
        {
            if (!isset($songs_played_raw[$i+1]))
                break;

            if ($this->_ignoreSong($song_row['song']['text']))
                continue;

            $start_timestamp = $song_row['timestamp'];
            $end_timestamp = $songs_played_raw[$i+1]['timestamp'] - 1;

            $relevant_stats = array();
            foreach($stats as $stat_timestamp => $stat_value)
            {
                if ($stat_timestamp <= $start_timestamp - 30)
                    unset($stats[$stat_timestamp]);
                elseif ($stat_timestamp >= $end_timestamp + 30)
                    break;
                else
                    $relevant_stats[$stat_timestamp] = $stat_value;
            }

            if (count($relevant_stats) < 2)
                continue;

            $song_row['stat_start'] = array_shift($relevant_stats);
            $song_row['stat_end'] = array_pop($relevant_stats);
            $song_row['stat_delta'] = $song_row['stat_end'] - $song_row['stat_start'];

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

    protected function _ignoreSong($song_name)
    {
        if (empty($song_name))
            return true;
        if (stristr($song_name, 'Offline') !== false)
            return true;
        if (stristr($song_name, 'Sweeper') !== false)
            return true;
        if (stristr($song_name, 'Bumper') !== false)
            return true;

        return false;
    }

    public function timelineAction()
    {
        $threshold = strtotime('yesterday 00:00:00');

        // Get current events within threshold.
        $events = \Entity\Schedule::getEventsInRange($this->station->id, $threshold, time());

        // Get listenership statistics.
        $stats_raw = $this->em->createQuery('SELECT s FROM Entity\Statistic s WHERE s.timestamp >= :datetime')
            ->setParameter('datetime', date('Y-m-d G:i:s', $threshold))
            ->getArrayResult();

        $stats = array();
        $short_name = $this->station->short_name;

        foreach($stats_raw as $stat_row)
        {
            $stat_timestamp = $stat_row['timestamp']->getTimestamp();
            $stat_for_station = (int)$stat_row['total_stations'][$short_name];

            $stats[$stat_timestamp] = $stat_for_station;
        }

        // Get all songs played in timeline.
        $songs_played_raw = $this->em->createQuery('SELECT sh, s FROM Entity\SongHistory sh JOIN sh.song s WHERE sh.station_id = :station_id AND sh.timestamp >= :timestamp ORDER BY sh.timestamp ASC')
            ->setParameter('station_id', $this->station->id)
            ->setParameter('timestamp', $threshold)
            ->getArrayResult();

        $songs = array();

        foreach($songs_played_raw as $i => $song_row)
        {
            if (!isset($songs_played_raw[$i+1]))
                break;

            if ($this->_ignoreSong($song_row['song']['text']))
                continue;

            $start_timestamp = $song_row['timestamp'];
            $end_timestamp = $songs_played_raw[$i+1]['timestamp'] - 1;

            $relevant_stats = array();
            foreach($stats as $stat_timestamp => $stat_value)
            {
                if ($stat_timestamp < $start_timestamp - 20)
                    unset($stats[$stat_timestamp]);
                elseif ($stat_timestamp > $end_timestamp + 20)
                    break;
                else
                    $relevant_stats[$stat_timestamp] = $stat_value;
            }

            if (count($relevant_stats) < 2)
                continue;

            $song_row['stat_start'] = array_shift($relevant_stats);
            $song_row['stat_end'] = array_pop($relevant_stats);
            $song_row['stat_delta'] = $song_row['stat_end'] - $song_row['stat_start'];

            foreach($events as $event)
            {
                if ($event['end_time'] >= $start_timestamp && $event['start_time'] <= $end_timestamp)
                {
                    $song_row['event'] = $event;
                    break;
                }
            }

            $songs[] = $song_row;
        }

        $this->view->songs = $songs;

        $format = $this->_getParam('format', 'html');
        if ($format == 'csv')
        {
            $this->doNotRender();

            $export_all = array();
            $export_all[] = array('Date', 'Time', 'Listeners', 'Delta', 'Track', 'Artist', 'Event');

            foreach($songs as $song_row)
            {
                $export_row = array(
                    date('Y-m-d', $song_row['timestamp']),
                    date('g:ia', $song_row['timestamp']),
                    $song_row['stat_start'],
                    $song_row['stat_delta'],
                    ($song_row['song']['title']) ? $song_row['song']['title'] : $song_row['song']['text'],
                    $song_row['song']['artist'],
                    ($song_row['event']) ? $song_row['event']['title'] : '',
                );

                $export_all[] = $export_row;
            }

            \DF\Export::csv($export_all);
            return;
        }
    }

    public function addadminAction()
    {
        $this->doNotRender();

        $record = new StationManager;
        $record->email = $_REQUEST['email'];
        $record->station = $this->station;
        $record->save();

        $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'email' => NULL));
    }

    public function removeadminAction()
    {
        $this->doNotRender();
        
        $id_hash = $this->_getParam('id');

        $record = StationManager::getRepository()->findOneBy(array('station_id' => $this->station->id, 'id' => $id_hash));
        if ($record instanceof StationManager)
            $record->delete();

        $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
    }


}