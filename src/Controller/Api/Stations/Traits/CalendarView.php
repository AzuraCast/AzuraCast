<?php
namespace App\Controller\Api\Stations\Traits;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Cake\Chronos\Chronos;
use Psr\Http\Message\ResponseInterface;

trait CalendarView
{
    protected function renderEvents(
        ServerRequest $request,
        Response $response,
        array $scheduleItems,
        callable $rowRender
    ): ResponseInterface {
        $station = $request->getStation();
        $tz = new \DateTimeZone($station->getTimezone());

        $params = $request->getQueryParams();

        $startDateStr = substr($params['start'], 0, 10);
        $startDate = Chronos::createFromFormat('Y-m-d', $startDateStr, $tz)->subDay();

        $endDateStr = substr($params['end'], 0, 10);
        $endDate = Chronos::createFromFormat('Y-m-d', $endDateStr, $tz);

        $events = [];

        foreach ($scheduleItems as $scheduleItem) {
            /** @var Entity\StationSchedule $scheduleItem */
            $i = $startDate;

            while ($i <= $endDate) {
                $dayOfWeek = $i->format('N');

                if ($scheduleItem->shouldPlayOnCurrentDate($i)
                    && $scheduleItem->isScheduledToPlayToday($dayOfWeek)) {
                    $rowStart = Entity\StationSchedule::getDateTime($scheduleItem->getStartTime(), $i);
                    $rowEnd = Entity\StationSchedule::getDateTime($scheduleItem->getEndTime(), $i);

                    // Handle overnight schedule items
                    if ($rowEnd < $rowStart) {
                        $rowEnd = $rowEnd->addDay();
                    }

                    $events[] = $rowRender($scheduleItem, $rowStart, $rowEnd);
                }

                $i = $i->addDay();
            }
        }

        return $response->withJson($events);
    }
}