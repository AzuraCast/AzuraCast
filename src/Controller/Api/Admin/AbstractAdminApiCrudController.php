<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

/**
 * @template TEntity as object
 * @extends AbstractApiCrudController<TEntity>
 */
abstract class AbstractAdminApiCrudController extends AbstractApiCrudController
{
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $query = $this->em->createQuery('SELECT e FROM ' . $this->entityClass . ' e');

        return $this->listPaginatedFromQuery($request, $response, $query);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     *
     * @throws Exception
     */
    public function createAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $row = $this->createRecord((array)$request->getParsedBody());

        $return = $this->viewRecord($row, $request);
        return $response->withJson($return);
    }

    /**
     * @param array $data
     *
     * @return TEntity
     */
    protected function createRecord(array $data): object
    {
        return $this->editRecord($data);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param mixed $id
     */
    public function getAction(ServerRequest $request, Response $response, mixed $id): ResponseInterface
    {
        $record = $this->getRecord($id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $return = $this->viewRecord($record, $request);
        return $response->withJson($return);
    }

    /**
     * @param mixed $id
     *
     * @return TEntity|null
     */
    protected function getRecord(mixed $id): ?object
    {
        return $this->em->find($this->entityClass, $id);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param mixed $id
     */
    public function editAction(ServerRequest $request, Response $response, mixed $id): ResponseInterface
    {
        $record = $this->getRecord($id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $this->editRecord((array)$request->getParsedBody(), $record);

        return $response->withJson(new Entity\Api\Status(true, __('Changes saved successfully.')));
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param mixed $id
     */
    public function deleteAction(ServerRequest $request, Response $response, mixed $id): ResponseInterface
    {
        $record = $this->getRecord($id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $this->deleteRecord($record);

        return $response->withJson(new Entity\Api\Status(true, __('Record deleted successfully.')));
    }
}
