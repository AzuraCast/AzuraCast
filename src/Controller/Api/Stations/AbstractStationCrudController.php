<?php
namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractCrudController;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use App\Utilities;
use Azura\Doctrine\Paginator;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractStationCrudController extends AbstractCrudController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param $station_id
     * @return ResponseInterface
     * @throws \Azura\Exception
     */
    public function listAction(Request $request, Response $response, $station_id): ResponseInterface
    {
        $station = $request->getStation();

        $query = $this->em->createQuery('SELECT e 
            FROM ' . $this->entityClass . ' e 
            WHERE e.station = :station')
            ->setParameter('station', $station);

        $paginator = new Paginator($query);
        $paginator->setFromRequest($request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = $request->getRouter();

        $paginator->setPostprocessor(function($row) use ($is_bootgrid, $router) {
            $return = $this->_viewRecord($row);
            $return['links'] = [
                'self' => (string)$router->fromHere($this->resourceRouteName, ['id' => $row->getId()], [], true),
            ];

            if ($is_bootgrid) {
                return Utilities::flatten_array($return, '_');
            }

            return $return;
        });

        return $paginator->write($response);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $station_id
     * @return ResponseInterface
     */
    public function createAction(Request $request, Response $response, $station_id): ResponseInterface
    {
        $station = $request->getStation();
        $record = new $this->entityClass($station);

        $row = $this->_createRecord($request->getParsedBody(), $record);

        $router = $request->getRouter();
        $return = $this->_viewRecord($row);
        $return['links'] = [
            'self' => (string)$router->fromHere($this->resourceRouteName, ['id' => $row->getId()], [], true),
        ];

        return $response->withJson($return);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $station_id
     * @param $record_id
     * @return ResponseInterface
     */
    public function editAction(Request $request, Response $response, $station_id, $record_id): ResponseInterface
    {
        $record = $this->_getRecord($request->getStation(), $record_id);

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
     * @param $station_id
     * @param $record_id
     * @return ResponseInterface
     */
    public function deleteAction(Request $request, Response $response, $station_id, $record_id): ResponseInterface
    {
        $record = $this->_getRecord($request->getStation(), $record_id);

        if (null === $record) {
            return $response
                ->withStatus(404)
                ->withJson(new Entity\Api\Error(404, 'Record not found!'));
        }

        $this->_deleteRecord($record);

        return $response->withJson(new Entity\Api\Status(true, 'Record deleted successfully.'));
    }

    /**
     * @param Entity\Station $station
     * @param $record_id
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
}
