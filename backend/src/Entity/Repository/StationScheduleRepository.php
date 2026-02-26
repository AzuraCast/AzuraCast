<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Api\StationSchedule as ApiStationSchedule;
use App\Entity\ApiGenerator\ScheduleApiGenerator;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationSchedule;
use App\Entity\StationStreamer;
use App\Radio\AutoDJ\Scheduler;
use App\Utilities\DateRange;
use App\Utilities\Time;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

/**
 * @extends Repository<StationSchedule>
 */
final class StationScheduleRepository extends Repository
{
    protected string $entityClass = StationSchedule::class;

    public function __construct(
        private readonly Scheduler $scheduler,
        private readonly ScheduleApiGenerator $scheduleApiGenerator
    ) {
    }

    /**
     * @param StationPlaylist|StationStreamer $relation
     * @param array $items
     */
    public function setScheduleItems(
        StationPlaylist|StationStreamer $relation,
        array $items = []
    ): void {
        $rawScheduleItems = $this->findByRelation($relation);

        $scheduleItems = [];
        foreach ($rawScheduleItems as $row) {
            $scheduleItems[$row->id] = $row;
        }

        foreach ($items as $item) {
            if (isset($item['id'], $scheduleItems[$item['id']])) {
                $record = $scheduleItems[$item['id']];
                unset($scheduleItems[$item['id']]);
            } else {
                $record = new StationSchedule($relation);
            }

            $record->start_time = (int)$item['start_time'];
            $record->end_time = (int)$item['end_time'];
            $record->start_date = $item['start_date'];
            $record->end_date = $item['end_date'];
            $record->days = $item['days'] ?? [];
            $record->loop_once = $item['loop_once'] ?? false;

            $this->em->persist($record);
        }

        foreach ($scheduleItems as $row) {
            $this->em->remove($row);
        }

        $this->em->flush();
    }

    /**
     * @param StationPlaylist|StationStreamer $relation
     *
     * @return StationSchedule[]
     */
    public function findByRelation(StationPlaylist|StationStreamer $relation): array
    {
        if ($relation instanceof StationPlaylist) {
            return $this->repository->findBy(['playlist' => $relation]);
        }

        return $this->repository->findBy(['streamer' => $relation]);
    }

    /**
     * @param Station $station
     *
     * @return StationSchedule[]
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
     * @param Station $station
     * @param DateTimeImmutable|null $now
     *
     * @return ApiStationSchedule[]
     */
    public function getUpcomingSchedule(
        Station $station,
        ?DateTimeImmutable $now = null
    ): array {
        $stationTz = $station->getTimezoneObject();
        $now = CarbonImmutable::instance(Time::nowInTimezone($stationTz, $now));

        $startDate = $now->subDay();
        $endDate = $now->addDay()->addHour();

        $events = [];

        foreach ($this->getAllScheduledItemsForStation($station) as $scheduleItem) {
            /** @var StationSchedule $scheduleItem */
            $i = $startDate;

            while ($i <= $endDate) {
                $dayOfWeek = $i->dayOfWeekIso;

                if (
                    $this->scheduler->shouldSchedulePlayOnCurrentDate($scheduleItem, $stationTz, $i)
                    && $this->scheduler->isScheduleScheduledToPlayToday($scheduleItem, $dayOfWeek)
                ) {
                    $start = StationSchedule::getDateTime($scheduleItem->start_time, $stationTz, $i);
                    $end = StationSchedule::getDateTime($scheduleItem->end_time, $stationTz, $i);

                    // Handle overnight schedule items
                    if ($end < $start) {
                        $end = $end->addDay();

                        // For overnight schedules, verify the event end doesn't exceed the configured end_date
                        if (!empty($scheduleItem->end_date)) {
                            $configuredEndDate = CarbonImmutable::createFromFormat(
                                'Y-m-d',
                                $scheduleItem->end_date,
                                $stationTz
                            );
                            if (null !== $configuredEndDate) {
                                // Allow one extra day if start_date == end_date (single overnight event)
                                if ($scheduleItem->start_date === $scheduleItem->end_date) {
                                    $configuredEndDate = $configuredEndDate->addDay();
                                }
                                $maxEndDateTime = StationSchedule::getDateTime(
                                    $scheduleItem->end_time,
                                    $stationTz,
                                    $configuredEndDate
                                );

                                if ($end->greaterThan($maxEndDateTime)) {
                                    $i = $i->addDay();
                                    continue; // Skip this event - it exceeds the configured date range
                                }
                            }
                        }
                    }

                    // Skip events that have already happened today.
                    if ($end->lessThan($now)) {
                        $i = $i->addDay();
                        continue;
                    }

                    $events[] = ($this->scheduleApiGenerator)(
                        $station,
                        $scheduleItem,
                        new DateRange($start, $end),
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
