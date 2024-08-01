<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Entity\StationSchedule;
use App\Radio\AutoDJ\Scheduler;
use App\Utilities\DateRange;
use Carbon\CarbonInterface;

trait HasScheduleDisplay
{
    use AcceptsDateRange;

    protected function getEvents(
        DateRange $dateRange,
        CarbonInterface $now,
        Scheduler $scheduler,
        array $scheduleItems,
        callable $rowRender
    ): array {
        $events = [];

        $loopStartDate = $dateRange->getStart()->subDay()->startOf('day');
        $loopEndDate = $dateRange->getEnd()->endOf('day');

        foreach ($scheduleItems as $scheduleItem) {
            /** @var StationSchedule $scheduleItem */
            $i = $loopStartDate;

            while ($i <= $loopEndDate) {
                $dayOfWeek = $i->dayOfWeekIso;

                if (
                    $scheduler->shouldSchedulePlayOnCurrentDate($scheduleItem, $i)
                    && $scheduler->isScheduleScheduledToPlayToday($scheduleItem, $dayOfWeek)
                ) {
                    $rowStart = StationSchedule::getDateTime($scheduleItem->getStartTime(), $i);
                    $rowEnd = StationSchedule::getDateTime($scheduleItem->getEndTime(), $i);

                    // Handle overnight schedule items
                    if ($rowEnd < $rowStart) {
                        $rowEnd = $rowEnd->addDay();
                    }

                    $itemDateRange = new DateRange($rowStart, $rowEnd);
                    if ($itemDateRange->isWithin($dateRange)) {
                        $events[] = $rowRender($scheduleItem, $rowStart, $rowEnd, $now);
                    }
                }

                $i = $i->addDay();
            }
        }

        return $events;
    }
}
