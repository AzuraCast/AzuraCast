<?php
namespace Repository;

use App\Doctrine\Repository;
use Entity\SongHistory as Record;

class SongHistoryRepository extends Repository
{
    /**
     * @param int $num_entries
     * @return array
     */
    public function getHistoryForStation(\Entity\Station $station, $num_entries = 5)
    {
        $history = $this->_em->createQuery('SELECT sh, s FROM Entity\SongHistory sh JOIN sh.song s WHERE sh.station_id = :station_id ORDER BY sh.id DESC')
            ->setParameter('station_id', $station->id)
            ->setMaxResults($num_entries)
            ->getArrayResult();

        $return = array();
        foreach($history as $sh)
        {
            $history = array(
                'played_at'     => $sh['timestamp_start'],
                'song'          => \Entity\Song::api($sh['song']),
            );
            $return[] = $history;
        }

        return $return;
    }

    /**
     * @param \Entity\Song $song
     * @param \Entity\Station $station
     * @param $np
     * @return Record|null
     */
    public function register(\Entity\Song $song, \Entity\Station $station, $np)
    {
        // Pull the most recent history item for this station.
        $last_sh = $this->_em->createQuery('SELECT sh FROM Entity\SongHistory sh
            WHERE sh.station_id = :station_id
            ORDER BY sh.timestamp_start DESC')
            ->setParameter('station_id', $station->id)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        $listeners = (int)$np['listeners']['current'];

        if ($last_sh->song_id == $song->id)
        {
            // Updating the existing SongHistory item with a new data point.
            $delta_points = (array)$last_sh->delta_points;
            $delta_points[] = $listeners;

            $last_sh->delta_points = $delta_points;

            $this->_em->persist($last_sh);
            $this->_em->flush();
            return null;
        } else
        {
            // Wrapping up processing on the previous SongHistory item (if present).
            if ($last_sh instanceof self)
            {
                $last_sh->timestamp_end = time();
                $last_sh->listeners_end = $listeners;

                // Calculate "delta" data for previous item, based on all data points.
                $delta_points = (array)$last_sh->delta_points;
                $delta_points[] = $listeners;
                $last_sh->delta_points = $delta_points;

                $delta_positive = 0;
                $delta_negative = 0;
                $delta_total = 0;

                for ($i = 1; $i < count($delta_points); $i++)
                {
                    $current_delta = $delta_points[$i];
                    $previous_delta = $delta_points[$i - 1];

                    $delta_delta = $current_delta - $previous_delta;
                    $delta_total += $delta_delta;

                    if ($delta_delta > 0)
                        $delta_positive += $delta_delta;
                    elseif ($delta_delta < 0)
                        $delta_negative += abs($delta_delta);
                }

                $last_sh->delta_positive = $delta_positive;
                $last_sh->delta_negative = $delta_negative;
                $last_sh->delta_total = $delta_total;

                $this->_em->persist($last_sh);
            }

            // Processing a new SongHistory item.
            $sh = new Record;
            $sh->song = $song;
            $sh->station = $station;

            $sh->listeners_start = $listeners;
            $sh->delta_points = [$listeners];

            $this->_em->persist($sh);
            $this->_em->flush();

            return $sh;
        }
    }
}