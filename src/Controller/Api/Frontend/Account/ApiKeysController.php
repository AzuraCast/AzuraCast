<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Security\SplitToken;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @template TEntity as Entity\ApiKey
 * @extends AbstractApiCrudController<TEntity>
 */
final class ApiKeysController extends AbstractApiCrudController
{
    protected string $entityClass = Entity\ApiKey::class;
    protected string $resourceRouteName = 'api:frontend:api-key';

    public function listAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $query = $this->em->createQuery(
            <<<'DQL'
            SELECT e FROM App\Entity\ApiKey e WHERE e.user = :user
        DQL
        )->setParameter('user', $request->getUser());

        return $this->listPaginatedFromQuery($request, $response, $query);
    }

    public function createAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $newKey = SplitToken::generate();

        $record = new Entity\ApiKey(
            $request->getUser(),
            $newKey
        );

        /** @var TEntity $record */
        $this->editRecord((array)$request->getParsedBody(), $record);

        $return = $this->viewRecord($record, $request);
        $return['key'] = (string)$newKey;

        return $response->withJson($return);
    }

    public function getAction(
        ServerRequest $request,
        Response $response,
        string $id
    ): ResponseInterface {
        $record = $this->getRecord($request->getUser(), $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $return = $this->viewRecord($record, $request);
        return $response->withJson($return);
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        string $id
    ): ResponseInterface {
        $record = $this->getRecord($request->getUser(), $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $this->deleteRecord($record);

        return $response->withJson(Entity\Api\Status::deleted());
    }

    /**
     * @param string $id
     *
     * @return TEntity|null
     */
    private function getRecord(Entity\User $user, string $id): ?object
    {
        /** @var TEntity|null $record */
        $record = $this->em->getRepository(Entity\ApiKey::class)->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);
        return $record;
    }

    /**
     * @inheritDoc
     */
    protected function editRecord(?array $data, ?object $record = null, array $context = []): object
    {
        $context[AbstractNormalizer::GROUPS] = [
            Entity\Interfaces\EntityGroupsInterface::GROUP_GENERAL,
        ];

        return parent::editRecord($data, $record, $context);
    }

    /**
     * @param TEntity $record
     * @param array<string, mixed> $context
     *
     * @return array<mixed>
     */
    protected function toArray(object $record, array $context = []): array
    {
        $context[AbstractNormalizer::GROUPS] = [
            Entity\Interfaces\EntityGroupsInterface::GROUP_ID,
            Entity\Interfaces\EntityGroupsInterface::GROUP_GENERAL,
        ];

        return parent::toArray($record, $context);
    }
}
