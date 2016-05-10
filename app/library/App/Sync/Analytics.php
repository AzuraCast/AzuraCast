<?php
namespace App\Sync;

class Analytics
{
    public static function sync()
    {
        $di = \Phalcon\Di::getDefault();
        $em = $di->get('em');

        // Clear out any non-daily statistics.
        $em->createQuery('DELETE FROM Entity\Analytics a WHERE a.type != :type')
            ->setParameter('type', 'day')
            ->execute();

        // Pull statistics in from influx.
        $influx = $di->get('influx');
        $daily_stats = $influx->query('SELECT * FROM /1d.*/ WHERE time > now() - 14d', 's');

        $new_records = array();
        $earliest_timestamp = time();

        foreach($daily_stats as $stat_series => $stat_rows)
        {
            $series_split = explode('.', $stat_series);
            $station_id = ($series_split[1] == 'all') ? NULL : $series_split[2];

            foreach($stat_rows as $stat_row)
            {
                if ($stat_row['time'] < $earliest_timestamp)
                    $earliest_timestamp = $stat_row['time'];

                $new_records[] = array(
                    'station_id' => $station_id,
                    'type' => 'day',
                    'timestamp' => $stat_row['time'],
                    'number_min' => (int)$stat_row['min'],
                    'number_max' => (int)$stat_row['max'],
                    'number_avg' => round($stat_row['value']),
                );
            }
        }

        $em->createQuery('DELETE FROM Entity\Analytics a WHERE a.timestamp >= :earliest')
            ->setParameter('earliest', $earliest_timestamp)
            ->execute();

        foreach($new_records as $new_record)
        {
            $row = new \Entity\Analytics;
            $row->fromArray($new_record);
            $em->persist($row);
        }

        $em->flush();
    }
}