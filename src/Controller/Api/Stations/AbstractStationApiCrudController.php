<?php

namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

abstract class AbstractStationApiCrudController extends AbstractApiCrudController
{
    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $this->getStation($request);

        $query = $this->em->createQuery('SELECT e
            FROM ' . $this->entityClass . ' e
            WHERE e.station = :station')
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

    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function createAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $this->getStation($request);
        $row = $this->createRecord($request->getParsedBody(), $station);

        $return = $this->viewRecord($row, $request);

        return $response->withJson($return);
    }

    /**
     * @param array $data
     * @param Entity\Station $station
     */
    protected function createRecord($data, Entity\Station $station): object
    {
        return $this->editRecord($data, null, [
            AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                $this->entityClass => [
                    'station' => $station,
                ],
            ],
        ]);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param int|string $station_id
     * @param int|string $id
     *
     * @throws Exception
     */
    public function getAction(ServerRequest $request, Response $response, $station_id, $id): ResponseInterface
    {
        $station = $this->getStation($request);
        $record = $this->getRecord($station, $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $return = $this->viewRecord($record, $request);
        return $response->withJson($return);
    }

    /**
     * @param Entity\Station $station
     * @param int|string $id
     */
    protected function getRecord(Entity\Station $station, $id): ?object
    {
        $repo = $this->em->getRepository($this->entityClass);
        return $repo->findOneBy([
            'station' => $station,
            'id' => $id,
        ]);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param int|string $station_id
     * @param int|string $id
     */
    public function editAction(ServerRequest $request, Response $response, $station_id, $id): ResponseInterface
    {
        $record = $this->getRecord($this->getStation($request), $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $this->editRecord($request->getParsedBody(), $record);

        return $response->withJson(new Entity\Api\Status(true, __('Changes saved successfully.')));
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param int|string $station_id
     * @param int|string $id
     */
    public function deleteAction(ServerRequest $request, Response $response, $station_id, $id): ResponseInterface
    {
        $record = $this->getRecord($this->getStation($request), $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $this->deleteRecord($record);

        return $response->withJson(new Entity\Api\Status(true, __('Record deleted successfully.')));
    }
}
