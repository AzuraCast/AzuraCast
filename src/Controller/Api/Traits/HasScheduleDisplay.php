<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Entity\StationSchedule;
use App\Http\ServerRequest;
use App\Radio\AutoDJ\Scheduler;
use App\Utilities\DateRange;
use Carbon\CarbonInterface;

trait HasScheduleDisplay
{
    use AcceptsDateRange;

    protected function getScheduleDateRange(ServerRequest $request): DateRange
    {
        $tz = $request->getStation()->getTimezoneObject();
        $dateRange = $this->getDateRange($request, $tz);

        return new DateRange(
            $dateRange->getStart()->subDay()->startOf('day'),
            $dateRange->getEnd()->endOf('day')
        );
    }

    protected function getEvents(
        DateRange $dateRange,
        CarbonInterface $now,
        Scheduler $scheduler,
        array $scheduleItems,
        callable $rowRender
    ): array {
        $events = [];

        $startDate = $dateRange->getStart();
        $endDate = $dateRange->getEnd();

        foreach ($scheduleItems as $scheduleItem) {
            /** @var StationSchedule $scheduleItem */
            $i = $startDate;

            while ($i <= $endDate) {
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

                    $events[] = $rowRender($scheduleItem, $rowStart, $rowEnd, $now);
                }

                $i = $i->addDay();
            }
        }

        return $events;
    }
}
