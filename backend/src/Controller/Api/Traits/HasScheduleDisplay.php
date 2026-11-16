<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Entity\Station;
use App\Entity\StationSchedule;
use App\Radio\AutoDJ\Scheduler;
use App\Utilities\DateRange;

trait HasScheduleDisplay
{
    use AcceptsDateRange;

    /**
     * @param StationSchedule[] $scheduleItems
     */
    protected function getEvents(
        Station $station,
        DateRange $dateRange,
        Scheduler $scheduler,
        array $scheduleItems,
        callable $rowRender
    ): array {
        $tz = $station->getTimezoneObject();

        $events = [];

        foreach ($scheduleItems as $scheduleItem) {
            foreach ($scheduler->getScheduleWindowsInRange($scheduleItem, $tz, $dateRange) as $window) {
                $events[] = $rowRender($station, $scheduleItem, $window);
            }
        }

        return $events;
    }
}
