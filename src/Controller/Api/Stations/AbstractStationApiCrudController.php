<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Station;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @template TEntity as object
 * @extends AbstractApiCrudController<TEntity>
 */
abstract class AbstractStationApiCrudController extends AbstractApiCrudController
{
    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $this->getStation($request);

        $query = $this->em->createQuery(
            'SELECT e
            FROM ' . $this->entityClass . ' e
            WHERE e.station = :station'
        )
            ->setParameter('station', $station);

        return $this->listPaginatedFromQuery($request, $response, $query);
    }

    /**
     * @return TEntity
     */
    protected function createRecord(ServerRequest $request, array $data): object
    {
        return $this->editRecord(
            $data,
            null,
            [
                AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                    $this->entityClass => [
                        'station' => $request->getStation(),
                    ],
                ],
            ]
        );
    }

    /**
     * @return TEntity
     */
    protected function getRecord(
        ServerRequest $request,
        array $params
    ): ?object {
        $station = $request->getStation();

        /** @var int|string $id */
        $id = $params['id'];

        return $this->em->getRepository($this->entityClass)->findOneBy(
            [
                'station' => $station,
                'id' => $id,
            ]
        );
    }

    /**
     * A placeholder function to retrieve the current station that some controllers can
     * override to verify that the station can perform the specified task.
     *
     * @param ServerRequest $request
     */
    protected function getStation(ServerRequest $request): Station
    {
        return $request->getStation();
    }
}
