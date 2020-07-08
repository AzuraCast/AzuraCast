<?php
namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeZone;
use InvalidArgumentException;

class StationScheduleRepository extends Repository
{
    /**
     * @param Entity\StationPlaylist|Entity\StationStreamer $relation
     * @param array|null $items
     */
    public function setScheduleItems($relation, ?array $items): void
    {
        $rawScheduleItems = $this->findByRelation($relation);

        $scheduleItems = [];
        foreach ($rawScheduleItems as $row) {
            $scheduleItems[$row->getId()] = $row;
        }

        foreach ($items as $item) {
            if (isset($item['id'], $scheduleItems[$item['id']])) {
                $record = $scheduleItems[$item['id']];
                unset($scheduleItems[$item['id']]);
            } else {
                $record = new Entity\StationSchedule($relation);
            }

            $record->setStartTime($item['start_time']);
            $record->setEndTime($item['end_time']);
            $record->setStartDate($item['start_date']);
            $record->setEndDate($item['end_date']);
            $record->setDays($item['days']);

            $this->em->persist($record);
        }

        foreach ($scheduleItems as $row) {
            $this->em->remove($row);
        }

        $this->em->flush();
    }

    /**
     * @param Entity\StationPlaylist|Entity\StationStreamer $relation
     *
     * @return Entity\StationSchedule[]
     */
    public function findByRelation($relation): array
    {
        if ($relation instanceof Entity\StationPlaylist) {
            return $this->repository->findBy(['playlist' => $relation]);
        }
        if ($relation instanceof Entity\StationStreamer) {
            return $this->repository->findBy(['streamer' => $relation]);
        }

        throw new InvalidArgumentException('Related entity must be a Playlist or Streamer.');
    }

    /**
     * @param Entity\Station $station
     * @param CarbonInterface|null $now
     *
     * @return Entity\Api\StationSchedule[]
     */
    public function getUpcomingSchedule(Entity\Station $station, CarbonInterface $now = null): array
    {
        if (null === $now) {
            $now = CarbonImmutable::now(new DateTimeZone($station->getTimezone()));
        }

        $startDate = $now->subDay();
        $endDate = $now->addDay()->addHour();

        $events = [];

        $scheduleItems = $this->em->createQuery(/** @lang DQL */ 'SELECT
                ssc, sp, sst
                FROM App\Entity\StationSchedule ssc
                LEFT JOIN ssc.playlist sp
                LEFT JOIN ssc.streamer sst
                WHERE (sp.station = :station AND sp.is_jingle = 0 AND sp.is_enabled = 1)
                OR (sst.station = :station AND sst.is_active = 1)
            ')->setParameter('station', $station)
            ->execute();

        foreach ($scheduleItems as $scheduleItem) {
            /** @var Entity\StationSchedule $scheduleItem */
            $i = $startDate;

            while ($i <= $endDate) {
                $dayOfWeek = $i->format('N');

                if ($scheduleItem->shouldPlayOnCurrentDate($i)
                    && $scheduleItem->isScheduledToPlayToday($dayOfWeek)) {
                    $start = Entity\StationSchedule::getDateTime($scheduleItem->getStartTime(), $i);
                    $end = Entity\StationSchedule::getDateTime($scheduleItem->getEndTime(), $i);

                    // Handle overnight schedule items
                    if ($end < $start) {
                        $end = $end->addDay();
                    }

                    // Skip events that have already happened today.
                    if ($end->lessThan($now)) {
                        $i = $i->addDay();
                        continue;
                    }

                    $row = new Entity\Api\StationSchedule;
                    $row->id = $scheduleItem->getId();
                    $row->start_timestamp = $start->getTimestamp();
                    $row->start = $start->toIso8601String();
                    $row->end_timestamp = $end->getTimestamp();
                    $row->end = $end->toIso8601String();
                    $row->is_now = $start->lessThanOrEqualTo($startDate);

                    if ($scheduleItem->getPlaylist() instanceof Entity\StationPlaylist) {
                        $playlist = $scheduleItem->getPlaylist();

                        $row->type = Entity\Api\StationSchedule::TYPE_PLAYLIST;
                        $row->name = $playlist->getName();
                    } elseif ($scheduleItem->getStreamer() instanceof Entity\StationStreamer) {
                        $streamer = $scheduleItem->getStreamer();

                        $row->type = Entity\Api\StationSchedule::TYPE_STREAMER;
                        $row->name = $streamer->getDisplayName();
                    }

                    $events[] = $row;
                }

                $i = $i->addDay();
            }
        }

        usort($events, function ($a, $b) {
            return $a->start_timestamp <=> $b->start_timestamp;
        });

        return $events;
    }
}