<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Doctrine\Repository;
use App\Entity\ApiGenerator\ScheduleApiGenerator;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationSchedule;
use App\Entity\StationStreamer;
use App\Radio\AutoDJ\Scheduler;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * @extends Repository<\App\Entity\StationSchedule>
 */
final class StationScheduleRepository extends Repository
{
    public function __construct(
        ReloadableEntityManagerInterface $em,
        private readonly Scheduler $scheduler,
        private readonly ScheduleApiGenerator $scheduleApiGenerator
    ) {
        parent::__construct($em);
    }

    /**
     * @param \App\Entity\StationPlaylist|\App\Entity\StationStreamer $relation
     * @param array $items
     */
    public function setScheduleItems(
        StationPlaylist|StationStreamer $relation,
        array $items = []
    ): void {
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
                $record = new StationSchedule($relation);
            }

            $record->setStartTime((int)$item['start_time']);
            $record->setEndTime((int)$item['end_time']);
            $record->setStartDate($item['start_date']);
            $record->setEndDate($item['end_date']);
            $record->setDays($item['days'] ?? []);
            $record->setLoopOnce($item['loop_once'] ?? false);

            $this->em->persist($record);
        }

        foreach ($scheduleItems as $row) {
            $this->em->remove($row);
        }

        $this->em->flush();
    }

    /**
     * @param \App\Entity\StationPlaylist|\App\Entity\StationStreamer $relation
     *
     * @return \App\Entity\StationSchedule[]
     */
    public function findByRelation(StationPlaylist|StationStreamer $relation): array
    {
        if ($relation instanceof StationPlaylist) {
            return $this->repository->findBy(['playlist' => $relation]);
        }

        return $this->repository->findBy(['streamer' => $relation]);
    }

    /**
     * @param \App\Entity\Station $station
     *
     * @return \App\Entity\StationSchedule[]
     */
    public function getAllScheduledItemsForStation(Station $station): array
    {
        return $this->em->createQuery(
            <<<'DQL'
                SELECT ssc, sp, sst
                FROM App\Entity\StationSchedule ssc
                LEFT JOIN ssc.playlist sp
                LEFT JOIN ssc.streamer sst
                WHERE (sp.station = :station AND sp.is_jingle = 0 AND sp.is_enabled = 1)
                OR (sst.station = :station AND sst.is_active = 1)
            DQL
        )->setParameter('station', $station)
            ->execute();
    }

    /**
     * @param \App\Entity\Station $station
     * @param CarbonInterface|null $now
     *
     * @return \App\Entity\Api\StationSchedule[]
     */
    public function getUpcomingSchedule(Station $station, CarbonInterface $now = null): array
    {
        if (null === $now) {
            $now = CarbonImmutable::now($station->getTimezoneObject());
        }

        $startDate = $now->subDay();
        $endDate = $now->addDay()->addHour();

        $events = [];

        foreach ($this->getAllScheduledItemsForStation($station) as $scheduleItem) {
            /** @var \App\Entity\StationSchedule $scheduleItem */
            $i = $startDate;

            while ($i <= $endDate) {
                $dayOfWeek = $i->dayOfWeekIso;

                if (
                    $this->scheduler->shouldSchedulePlayOnCurrentDate($scheduleItem, $i)
                    && $this->scheduler->isScheduleScheduledToPlayToday($scheduleItem, $dayOfWeek)
                ) {
                    $start = StationSchedule::getDateTime($scheduleItem->getStartTime(), $i);
                    $end = StationSchedule::getDateTime($scheduleItem->getEndTime(), $i);

                    // Handle overnight schedule items
                    if ($end < $start) {
                        $end = $end->addDay();
                    }

                    // Skip events that have already happened today.
                    if ($end->lessThan($now)) {
                        $i = $i->addDay();
                        continue;
                    }

                    $events[] = ($this->scheduleApiGenerator)(
                        $scheduleItem,
                        $start,
                        $end,
                        $now
                    );
                }

                $i = $i->addDay();
            }
        }

        usort(
            $events,
            static function ($a, $b) {
                return $a->start_timestamp <=> $b->start_timestamp;
            }
        );

        return $events;
    }
}
