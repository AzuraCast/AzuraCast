<?php

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\AutoDJ\Scheduler;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractScheduledEntityController extends AbstractStationApiCrudController
{
    protected Entity\Repository\StationScheduleRepository $scheduleRepo;

    protected Scheduler $scheduler;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\StationScheduleRepository $scheduleRepo,
        Scheduler $scheduler
    ) {
        parent::__construct($em, $serializer, $validator);

        $this->scheduleRepo = $scheduleRepo;
        $this->scheduler = $scheduler;
    }

    protected function renderEvents(
        ServerRequest $request,
        Response $response,
        array $scheduleItems,
        callable $rowRender
    ): ResponseInterface {
        $station = $request->getStation();
        $tz = $station->getTimezoneObject();

        $params = $request->getQueryParams();

        $startDateStr = substr($params['start'], 0, 10);
        $startDate = CarbonImmutable::createFromFormat('Y-m-d', $startDateStr, $tz)->subDay();

        $endDateStr = substr($params['end'], 0, 10);
        $endDate = CarbonImmutable::createFromFormat('Y-m-d', $endDateStr, $tz);

        $events = [];

        foreach ($scheduleItems as $scheduleItem) {
            /** @var Entity\StationSchedule $scheduleItem */
            $i = $startDate;

            while ($i <= $endDate) {
                $dayOfWeek = $i->dayOfWeekIso;

                if (
                    $this->scheduler->shouldSchedulePlayOnCurrentDate($scheduleItem, $i)
                    && $this->scheduler->isScheduleScheduledToPlayToday($scheduleItem, $dayOfWeek)
                ) {
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

    /**
     * @inheritDoc
     */
    protected function fromArray($data, $record = null, array $context = []): object
    {
        $scheduleItems = $data['schedule_items'] ?? null;
        unset($data['schedule_items']);

        $record = parent::fromArray($data, $record, $context);

        if ($record instanceof $this->entityClass) {
            $this->em->persist($record);
            $this->em->flush();

            if (null !== $scheduleItems) {
                $this->scheduleRepo->setScheduleItems($record, $scheduleItems);
            }
        }

        return $record;
    }
}
