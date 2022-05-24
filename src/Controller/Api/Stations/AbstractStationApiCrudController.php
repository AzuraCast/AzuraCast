<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
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
        string $station_id
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
     * A placeholder function to retrieve the current station that some controllers can
     * override to verify that the station can perform the specified task.
     *
     * @param ServerRequest $request
     */
    protected function getStation(ServerRequest $request): Entity\Station
    {
        return $request->getStation();
    }

    public function createAction(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $this->getStation($request);
        $row = $this->createRecord((array)$request->getParsedBody(), $station);

        $return = $this->viewRecord($row, $request);

        return $response->withJson($return);
    }

    /**
     * @param array $data
     * @param Entity\Station $station
     *
     * @return TEntity
     */
    protected function createRecord(array $data, Entity\Station $station): object
    {
        return $this->editRecord(
            $data,
            null,
            [
                AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                    $this->entityClass => [
                        'station' => $station,
                    ],
                ],
            ]
        );
    }

    public function getAction(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id
    ): ResponseInterface {
        $station = $this->getStation($request);
        $record = $this->getRecord($station, $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $return = $this->viewRecord($record, $request);
        return $response->withJson($return);
    }

    /**
     * @param Entity\Station $station
     * @param int|string $id
     *
     * @return TEntity
     */
    protected function getRecord(Entity\Station $station, int|string $id): ?object
    {
        return $this->em->getRepository($this->entityClass)->findOneBy(
            [
                'station' => $station,
                'id' => $id,
            ]
        );
    }

    public function editAction(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id
    ): ResponseInterface {
        $record = $this->getRecord($this->getStation($request), $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $this->editRecord((array)$request->getParsedBody(), $record);

        return $response->withJson(Entity\Api\Status::updated());
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id
    ): ResponseInterface {
        $record = $this->getRecord($this->getStation($request), $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $this->deleteRecord($record);

        return $response->withJson(Entity\Api\Status::deleted());
    }
}
