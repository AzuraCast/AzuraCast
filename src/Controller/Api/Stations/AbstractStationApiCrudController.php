<?php
namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractApiCrudController;
use App\Doctrine\Paginator;
use App\Entity;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

abstract class AbstractStationApiCrudController extends AbstractApiCrudController
{
    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
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

        $paginator->setPostprocessor(function ($row) use ($is_bootgrid, $router) {
            $return = $this->_viewRecord($row, $router);
            if ($is_bootgrid) {
                return Utilities::flattenArray($return, '_');
            }

            return $return;
        });

        return $paginator->write($response);
    }

    /**
     * A placeholder function to retrieve the current station that some controllers can
     * override to verify that the station can perform the specified task.
     *
     * @param ServerRequest $request
     *
     * @return Entity\Station
     */
    protected function _getStation(ServerRequest $request): Entity\Station
    {
        return $request->getStation();
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function createAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $this->_getStation($request);
        $row = $this->_createRecord($request->getParsedBody(), $station);

        $router = $request->getRouter();
        $return = $this->_viewRecord($row, $router);

        return $response->withJson($return);
    }

    /**
     * @param array $data
     * @param Entity\Station $station
     *
     * @return object
     */
    protected function _createRecord($data, Entity\Station $station): object
    {
        return $this->_editRecord($data, null, [
            AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                $this->entityClass => [
                    'station' => $station,
                ],
            ],
        ]);
    }

    protected function _editRecord($data, $record = null, array $context = []): object
    {
        // Force an unset of the `station` parameter as it supercedes the default constructor arguments.
        unset($data['station']);

        return parent::_editRecord($data, $record, $context);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param int|string $station_id
     * @param int|string $id
     *
     * @return ResponseInterface
     * @throws Exception
     */
    public function getAction(ServerRequest $request, Response $response, $station_id, $id): ResponseInterface
    {
        $station = $this->_getStation($request);
        $record = $this->_getRecord($station, $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $return = $this->_viewRecord($record, $request->getRouter());
        return $response->withJson($return);
    }

    /**
     * @param Entity\Station $station
     * @param int|string $id
     *
     * @return object|null
     */
    protected function _getRecord(Entity\Station $station, $id)
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
     *
     * @return ResponseInterface
     */
    public function editAction(ServerRequest $request, Response $response, $station_id, $id): ResponseInterface
    {
        $record = $this->_getRecord($this->_getStation($request), $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $this->_editRecord($request->getParsedBody(), $record);

        return $response->withJson(new Entity\Api\Status(true, __('Changes saved successfully.')));
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param int|string $station_id
     * @param int|string $id
     *
     * @return ResponseInterface
     */
    public function deleteAction(ServerRequest $request, Response $response, $station_id, $id): ResponseInterface
    {
        $record = $this->_getRecord($this->_getStation($request), $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $this->_deleteRecord($record);

        return $response->withJson(new Entity\Api\Status(true, __('Record deleted successfully.')));
    }
}
