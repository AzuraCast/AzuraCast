<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\AutoDJ\Scheduler;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @template TEntity as Entity\StationPlaylist|Entity\StationStreamer
 * @extends AbstractStationApiCrudController<TEntity>
 */
abstract class AbstractScheduledEntityController extends AbstractStationApiCrudController
{
    public function __construct(
        protected Entity\Repository\StationScheduleRepository $scheduleRepo,
        protected Scheduler $scheduler,
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    protected function renderEvents(
        ServerRequest $request,
        Response $response,
        array $scheduleItems,
        callable $rowRender
    ): ResponseInterface {
        $tz = $request->getStation()->getTimezoneObject();

        $params = $request->getQueryParams();

        $startDateStr = substr($params['start'], 0, 10);
        $startDate = CarbonImmutable::createFromFormat('Y-m-d', $startDateStr, $tz);

        if (false === $startDate) {
            throw new \InvalidArgumentException(sprintf('Could not parse start date: "%s"', $startDateStr));
        }

        $startDate = $startDate->subDay();

        $endDateStr = substr($params['end'], 0, 10);
        $endDate = CarbonImmutable::createFromFormat('Y-m-d', $endDateStr, $tz);

        if (false === $endDate) {
            throw new \InvalidArgumentException(sprintf('Could not parse end date: "%s"', $endDateStr));
        }

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

    protected function editRecord(?array $data, object $record = null, array $context = []): object
    {
        if (null === $data) {
            throw new InvalidArgumentException('Could not parse input data.');
        }

        $scheduleItems = $data['schedule_items'] ?? [];
        unset($data['schedule_items']);

        $record = $this->fromArray($data, $record, $context);

        $errors = $this->validator->validate($record);
        if (count($errors) > 0) {
            $e = new ValidationException((string)$errors);
            $e->setDetailedErrors($errors);
            throw $e;
        }

        $this->em->persist($record);
        $this->em->flush();

        if (null !== $scheduleItems) {
            $this->scheduleRepo->setScheduleItems($record, $scheduleItems);
        }

        return $record;
    }
}
