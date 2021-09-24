<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Entity;
use App\Http\ServerRequest;
use App\Radio\AutoDJ\Scheduler;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

trait HasScheduleDisplay
{
    protected function getDateRange(ServerRequest $request): array
    {
        $tz = $request->getStation()->getTimezoneObject();
        $params = $request->getQueryParams();

        $startDateStr = substr($params['start'], 0, 10);
        $startDate = CarbonImmutable::createFromFormat('Y-m-d', $startDateStr, $tz);

        if (false === $startDate) {
            throw new \InvalidArgumentException(sprintf('Could not parse start date: "%s"', $startDateStr));
        }

        $startDate = $startDate->startOf('day');

        $endDateStr = substr($params['end'], 0, 10);
        $endDate = CarbonImmutable::createFromFormat('Y-m-d', $endDateStr, $tz);

        if (false === $endDate) {
            throw new \InvalidArgumentException(sprintf('Could not parse end date: "%s"', $endDateStr));
        }

        $endDate = $endDate->endOf('day');

        return [$startDate, $endDate];
    }

    protected function getEvents(
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        CarbonInterface $now,
        Scheduler $scheduler,
        array $scheduleItems,
        callable $rowRender
    ): array {
        $events = [];

        foreach ($scheduleItems as $scheduleItem) {
            /** @var Entity\StationSchedule $scheduleItem */
            $i = $startDate;

            while ($i <= $endDate) {
                $dayOfWeek = $i->dayOfWeekIso;

                if (
                    $scheduler->shouldSchedulePlayOnCurrentDate($scheduleItem, $i)
                    && $scheduler->isScheduleScheduledToPlayToday($scheduleItem, $dayOfWeek)
                ) {
                    $rowStart = Entity\StationSchedule::getDateTime($scheduleItem->getStartTime(), $i);
                    $rowEnd = Entity\StationSchedule::getDateTime($scheduleItem->getEndTime(), $i);

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
