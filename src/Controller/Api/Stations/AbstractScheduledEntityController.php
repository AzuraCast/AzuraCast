<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\HasScheduleDisplay;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
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
 * @template TEntity as Entity\StationPlaylist|Entity\StationStreamer
 * @extends AbstractStationApiCrudController<TEntity>
 */
abstract class AbstractScheduledEntityController extends AbstractStationApiCrudController
{
    use HasScheduleDisplay;

    public function __construct(
        protected Entity\Repository\StationScheduleRepository $scheduleRepo,
        protected Scheduler $scheduler,
        ReloadableEntityManagerInterface $em,
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
