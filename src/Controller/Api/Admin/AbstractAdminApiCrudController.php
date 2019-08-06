<?php
namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use App\Utilities;
use Azura\Doctrine\Paginator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractAdminApiCrudController extends AbstractApiCrudController
{
    public function listAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $this->em->createQuery('SELECT e FROM ' . $this->entityClass . ' e');

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
     * @return ResponseInterface
     * @throws \Azura\Exception
     */
    public function createAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $row = $this->_createRecord($request->getParsedBody());

        $return = $this->_viewRecord($row, RequestHelper::getRouter($request));
        return ResponseHelper::withJson($response, $return);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param mixed $record_id
     * @return ResponseInterface
     */
    public function getAction(ServerRequestInterface $request, ResponseInterface $response, $record_id): ResponseInterface
    {
        $record = $this->_getRecord($record_id);

        $return = $this->_viewRecord($record, RequestHelper::getRouter($request));
        return ResponseHelper::withJson($response, $return);
    }

    /**
     * @param array $data
     * @return object
     */
    protected function _createRecord($data): object
    {
        return $this->_editRecord($data, null);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param mixed $record_id
     * @return ResponseInterface
     */
    public function editAction(ServerRequestInterface $request, ResponseInterface $response, $record_id): ResponseInterface
    {
        $record = $this->_getRecord($record_id);

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
     * @param mixed $record_id
     * @return ResponseInterface
     */
    public function deleteAction(ServerRequestInterface $request, ResponseInterface $response, $record_id): ResponseInterface
    {
        $record = $this->_getRecord($record_id);

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
