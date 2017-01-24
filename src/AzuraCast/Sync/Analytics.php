<?php
namespace AzuraCast\Sync;

use Doctrine\ORM\EntityManager;

class Analytics extends SyncAbstract
{
    public function run()
    {
        /** @var EntityManager $em */
        $em = $this->di['em'];

        // Clear out any non-daily statistics.
        $em->createQuery('DELETE FROM Entity\Analytics a WHERE a.type != :type')
            ->setParameter('type', 'day')
            ->execute();

        // Pull statistics in from influx.
        $influx = $this->di['influx'];

        $resultset = $influx->query('SELECT * FROM "1d"./.*/ WHERE time > now() - 14d', [
            'epoch' => 's',
        ]);

        $results_raw = $resultset->getSeries();
        $results = array();
        foreach($results_raw as $serie)
        {
            $points = [];
            foreach ($serie['values'] as $point)
                $points[] = array_combine($serie['columns'], $point);

            $results[$serie['name']] = $points;
        }

        $new_records = array();
        $earliest_timestamp = time();

        foreach($results as $stat_series => $stat_rows)
        {
            $series_split = explode('.', $stat_series);
            $station_id = ($series_split[1] == 'all') ? NULL : $series_split[1];

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
            $row->fromArray($em, $new_record);

            $em->persist($row);
        }

        $em->flush();
    }
}