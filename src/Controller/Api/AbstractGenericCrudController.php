<?php
namespace App\Controller\Api;

use App\Entity;
use App\Http\Request;
use App\Http\Response;
use App\Utilities;
use Azura\Doctrine\Paginator;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractGenericCrudController extends AbstractCrudController
{
    public function listAction(Request $request, Response $response): ResponseInterface
    {
        $query = $this->em->createQuery('SELECT e FROM ' . $this->entityClass . ' e');

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
     * @return ResponseInterface
     * @throws \Azura\Exception
     */
    public function createAction(Request $request, Response $response): ResponseInterface
    {
        $record = new $this->entityClass();
        $row = $this->_editRecord($request->getParsedBody(), $record);

        $return = $this->_viewRecord($row, $request->getRouter());
        return $response->withJson($return);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param mixed $record_id
     * @return ResponseInterface
     */
    public function getAction(Request $request, Response $response, $record_id): ResponseInterface
    {
        $record = $this->_getRecord($record_id);

        $return = $this->_viewRecord($record, $request->getRouter());
        return $response->withJson($return);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param mixed $record_id
     * @return ResponseInterface
     */
    public function editAction(Request $request, Response $response, $record_id): ResponseInterface
    {
        $record = $this->_getRecord($record_id);

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
     * @param mixed $record_id
     * @return ResponseInterface
     */
    public function deleteAction(Request $request, Response $response, $record_id): ResponseInterface
    {
        $record = $this->_getRecord($record_id);

        if (null === $record) {
            return $response
                ->withStatus(404)
                ->withJson(new Entity\Api\Error(404, 'Record not found!'));
        }

        $this->_deleteRecord($record);

        return $response->withJson(new Entity\Api\Status(true, 'Record deleted successfully.'));
    }

    /**
     * @param mixed $record_id
     * @return object|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    protected function _getRecord($record_id)
    {
        return $this->em->find($this->entityClass, $record_id);
    }
}
