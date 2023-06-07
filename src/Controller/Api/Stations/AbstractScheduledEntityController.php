<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\HasScheduleDisplay;
use App\Entity\Repository\StationScheduleRepository;
use App\Entity\StationPlaylist;
use App\Entity\StationStreamer;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\AutoDJ\Scheduler;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @template TEntity as StationPlaylist|StationStreamer
 * @extends AbstractStationApiCrudController<TEntity>
 */
abstract class AbstractScheduledEntityController extends AbstractStationApiCrudController
{
    use HasScheduleDisplay;

    public function __construct(
        protected StationScheduleRepository $scheduleRepo,
        protected Scheduler $scheduler,
        Serializer $serializer,
        ValidatorInterface $validator,
    ) {
        parent::__construct($serializer, $validator);
    }

    protected function renderEvents(
        ServerRequest $request,
        Response $response,
        array $scheduleItems,
        callable $rowRender
    ): ResponseInterface {
        $dateRange = $this->getScheduleDateRange($request);

        $station = $request->getStation();
        $now = CarbonImmutable::now($station->getTimezoneObject());

        $events = $this->getEvents($dateRange, $now, $this->scheduler, $scheduleItems, $rowRender);
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
            throw ValidationException::fromValidationErrors($errors);
        }

        $this->em->persist($record);
        $this->em->flush();

        if (null !== $scheduleItems) {
            $this->scheduleRepo->setScheduleItems($record, $scheduleItems);
        }

        return $record;
    }
}
