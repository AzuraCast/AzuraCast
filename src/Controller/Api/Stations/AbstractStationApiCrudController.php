<?php
namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use App\Utilities;
use Azura\Doctrine\Paginator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

abstract class AbstractStationApiCrudController extends AbstractApiCrudController
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int|string $station_id
     * @return ResponseInterface
     */
    public function listAction(ServerRequestInterface $request, ResponseInterface $response, $station_id): ResponseInterface
    {
        $station = $this->_getStation($request);

        $query = $this->em->createQuery('SELECT e 
            FROM ' . $this->entityClass . ' e 
            WHERE e.station = :station')
            ->setParameter('station', $station);

        $paginator = new Paginator($query);
        $paginator->setFromRequest($request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = RequestHelper::getRouter($request);

        $paginator->setPostprocessor(function($row) use ($is_bootgrid, $router) {
            $return = $this->_viewRecord($row, $router);
            if ($is_bootgrid) {
                return Utilities::flattenArray($return, '_');
            }

            return $return;
        });

        return $paginator->write($response);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int|string $station_id
     * @return ResponseInterface
     */
    public function createAction(ServerRequestInterface $request, ResponseInterface $response, $station_id): ResponseInterface
    {
        $station = $this->_getStation($request);
        $row = $this->_createRecord($request->getParsedBody(), $station);

        $router = RequestHelper::getRouter($request);
        $return = $this->_viewRecord($row, $router);

        return ResponseHelper::withJson($response, $return);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int|string $record_id
     * @return ResponseInterface
     */
    public function getAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $record_id): ResponseInterface
    {
        $station = $this->_getStation($request);
        $record = $this->_getRecord($station, $record_id);

        $return = $this->_viewRecord($record, RequestHelper::getRouter($request));
        return ResponseHelper::withJson($response, $return);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int|string $station_id
     * @param int|string $record_id
     * @return ResponseInterface
     */
    public function editAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $record_id): ResponseInterface
    {
        $record = $this->_getRecord($this->_getStation($request), $record_id);

        if (null === $record) {
            return ResponseHelper::withJson(
                $response->withStatus(404),
                new Entity\Api\Error(404, 'Record not found!')
            );
        }

        $this->_editRecord($request->getParsedBody(), $record);

        return ResponseHelper::withJson($response, new Entity\Api\Status(true, 'Changes saved successfully.'));
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int|string $station_id
     * @param int|string $record_id
     * @return ResponseInterface
     */
    public function deleteAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $record_id): ResponseInterface
    {
        $record = $this->_getRecord($this->_getStation($request), $record_id);

        if (null === $record) {
            return ResponseHelper::withJson(
                $response->withStatus(404),
                new Entity\Api\Error(404, 'Record not found!')
            );
        }

        $this->_deleteRecord($record);

        return ResponseHelper::withJson($response, new Entity\Api\Status(true, 'Record deleted successfully.'));
    }

    /**
     * @param array $data
     * @param Entity\Station $station
     * @return object
     */
    protected function _createRecord($data, Entity\Station $station): object
    {
        return $this->_editRecord($data, null, [
            AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                $this->entityClass => [
                    'station' => $station,
                ]
            ],
        ]);
    }

    /**
     * @param Entity\Station $station
     * @param int|string $record_id
     * @return object|null
     */
    protected function _getRecord(Entity\Station $station, $record_id)
    {
        $repo = $this->em->getRepository($this->entityClass);
        return $repo->findOneBy([
            'station' => $station,
            'id' => $record_id,
        ]);
    }

    /**
     * A placeholder function to retrieve the current station that some controllers can
     * override to verify that the station can perform the specified task.
     *
     * @param ServerRequestInterface $request
     * @return Entity\Station
     */
    protected function _getStation(ServerRequestInterface $request): Entity\Station
    {
        return RequestHelper::getStation($request);
    }
}
