<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractScheduledEntityController extends AbstractStationApiCrudController
{
    protected Entity\Repository\StationScheduleRepository $scheduleRepo;

    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\StationScheduleRepository $scheduleRepo
    ) {
        parent::__construct($em, $serializer, $validator);

        $this->scheduleRepo = $scheduleRepo;
    }

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

    /**
     * @inheritDoc
     */
    protected function _denormalizeToRecord($data, $record = null, array $context = []): object
    {
        $scheduleItems = $data['schedule_items'] ?? null;
        unset($data['schedule_items']);

        $record = parent::_denormalizeToRecord($data, $record, $context);

        if ($record instanceof $this->entityClass) {
            $this->em->persist($record);
            $this->em->flush($record);

            if (null !== $scheduleItems) {
                $this->scheduleRepo->setScheduleItems($record, $scheduleItems);
            }
        }

        return $record;
    }
}