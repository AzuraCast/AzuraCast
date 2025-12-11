<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Entity\Station;
use App\Entity\StationSchedule;
use App\Radio\AutoDJ\Scheduler;
use App\Utilities\DateRange;
use Carbon\CarbonImmutable;

trait HasScheduleDisplay
{
    use AcceptsDateRange;

    protected function getEvents(
        Station $station,
        DateRange $dateRange,
        Scheduler $scheduler,
        array $scheduleItems,
        callable $rowRender
    ): array {
        $tz = $station->getTimezoneObject();

        $events = [];

        $loopStartDate = $dateRange->start->subDay()->startOf('day');
        $loopEndDate = $dateRange->end->endOf('day');

        foreach ($scheduleItems as $scheduleItem) {
            /** @var StationSchedule $scheduleItem */
            $i = $loopStartDate;

            while ($i <= $loopEndDate) {
                $dayOfWeek = $i->dayOfWeekIso;

                if (
                    $scheduler->shouldSchedulePlayOnCurrentDate($scheduleItem, $tz, $i)
                    && $scheduler->isScheduleScheduledToPlayToday($scheduleItem, $dayOfWeek)
                ) {
                    $rowStart = StationSchedule::getDateTime($scheduleItem->start_time, $tz, $i);
                    $rowEnd = StationSchedule::getDateTime($scheduleItem->end_time, $tz, $i);

                    // Handle overnight schedule items
                    if ($rowEnd < $rowStart) {
                        $rowEnd = $rowEnd->addDay();

                        // For overnight schedules, verify the event end doesn't exceed the configured end_date
                        if (!empty($scheduleItem->end_date)) {
                            $configuredEndDate = CarbonImmutable::createFromFormat(
                                'Y-m-d',
                                $scheduleItem->end_date,
                                $tz
                            );
                            if (null !== $configuredEndDate) {
                                // Allow one extra day if start_date == end_date (single overnight event)
                                if ($scheduleItem->start_date === $scheduleItem->end_date) {
                                    $configuredEndDate = $configuredEndDate->addDay();
                                }
                                $maxEndDateTime = StationSchedule::getDateTime(
                                    $scheduleItem->end_time,
                                    $tz,
                                    $configuredEndDate
                                );

                                if ($rowEnd->greaterThan($maxEndDateTime)) {
                                    $i = $i->addDay();
                                    continue; // Skip this event - it exceeds the configured date range
                                }
                            }
                        }
                    }

                    $itemDateRange = new DateRange($rowStart, $rowEnd);
                    if ($itemDateRange->isWithin($dateRange)) {
                        $events[] = $rowRender($station, $scheduleItem, $itemDateRange);
                    }
                }

                $i = $i->addDay();
            }
        }

        return $events;
    }
}
