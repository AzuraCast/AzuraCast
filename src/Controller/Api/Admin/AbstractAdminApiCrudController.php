<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

/**
 * @template TEntity as object
 * @extends AbstractApiCrudController<TEntity>
 */
abstract class AbstractAdminApiCrudController extends AbstractApiCrudController
{
    public function listAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $query = $this->em->createQuery('SELECT e FROM ' . $this->entityClass . ' e');

        return $this->listPaginatedFromQuery($request, $response, $query);
    }

    public function createAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $row = $this->createRecord((array)$request->getParsedBody());

        $return = $this->viewRecord($row, $request);
        return $response->withJson($return);
    }

    /**
     * @param array $data
     * @return TEntity
     */
    protected function createRecord(array $data): object
    {
        return $this->editRecord($data);
    }

    public function getAction(
        ServerRequest $request,
        Response $response,
        string $id
    ): ResponseInterface {
        $record = $this->getRecord($id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $return = $this->viewRecord($record, $request);
        return $response->withJson($return);
    }

    /**
     * @param string $id
     *
     * @return TEntity|null
     */
    protected function getRecord(string $id): ?object
    {
        return $this->em->find($this->entityClass, $id);
    }

    public function editAction(
        ServerRequest $request,
        Response $response,
        string $id
    ): ResponseInterface {
        $record = $this->getRecord($id);

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
        string $id
    ): ResponseInterface {
        $record = $this->getRecord($id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $this->deleteRecord($record);

        return $response->withJson(Entity\Api\Status::deleted());
    }
}
