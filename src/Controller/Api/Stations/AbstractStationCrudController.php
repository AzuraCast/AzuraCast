<?php
namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractCrudController;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use App\Utilities;
use Azura\Doctrine\Paginator;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

abstract class AbstractStationCrudController extends AbstractCrudController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param int|string $station_id
     * @return ResponseInterface
     */
    public function listAction(Request $request, Response $response, $station_id): ResponseInterface
    {
        $station = $this->_getStation($request);

        $query = $this->em->createQuery('SELECT e 
            FROM ' . $this->entityClass . ' e 
            WHERE e.station = :station')
            ->setParameter('station', $station);

        $paginator = new Paginator($query);
        $paginator->setFromRequest($request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = $request->getRouter();

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
     * @param Request $request
     * @param Response $response
     * @param int|string $station_id
     * @return ResponseInterface
     */
    public function createAction(Request $request, Response $response, $station_id): ResponseInterface
    {
        $station = $this->_getStation($request);
        $row = $this->_createRecord($request->getParsedBody(), $station);

        $router = $request->getRouter();
        $return = $this->_viewRecord($row, $router);

        return $response->withJson($return);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param int|string $record_id
     * @return ResponseInterface
     */
    public function getAction(Request $request, Response $response, $station_id, $record_id): ResponseInterface
    {
        $station = $this->_getStation($request);
        $record = $this->_getRecord($station, $record_id);

        $return = $this->_viewRecord($record, $request->getRouter());
        return $response->withJson($return);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param int|string $station_id
     * @param int|string $record_id
     * @return ResponseInterface
     */
    public function editAction(Request $request, Response $response, $station_id, $record_id): ResponseInterface
    {
        $record = $this->_getRecord($this->_getStation($request), $record_id);

        if (null === $record) {
            return $response
                ->withStatus(404)
                ->withJson(new Entity\Api\Error(404, 'Record not found!'));
        }

        $this->_editRecord($request->getParsedBody(), $record);

        return $response->withJson(new Entity\Api\Status(true, 'Changes saved successfully.'));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param int|string $station_id
     * @param int|string $record_id
     * @return ResponseInterface
     */
    public function deleteAction(Request $request, Response $response, $station_id, $record_id): ResponseInterface
    {
        $record = $this->_getRecord($this->_getStation($request), $record_id);

        if (null === $record) {
            return $response
                ->withStatus(404)
                ->withJson(new Entity\Api\Error(404, 'Record not found!'));
        }

        $this->_deleteRecord($record);

        return $response->withJson(new Entity\Api\Status(true, 'Record deleted successfully.'));
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
     * @param Request $request
     * @return Entity\Station
     */
    protected function _getStation(Request $request): Entity\Station
    {
        return $request->getStation();
    }
}
