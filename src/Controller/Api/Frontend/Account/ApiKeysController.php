<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Entity\ApiKey;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Entity\User;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Security\SplitToken;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @template TEntity as ApiKey
 * @extends AbstractApiCrudController<TEntity>
 */
final class ApiKeysController extends AbstractApiCrudController
{
    protected string $entityClass = ApiKey::class;
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

        $record = new ApiKey(
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
                ->withJson(Error::notFound());
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
                ->withJson(Error::notFound());
        }

        $this->deleteRecord($record);

        return $response->withJson(Status::deleted());
    }

    /**
     * @param string $id
     *
     * @return TEntity|null
     */
    private function getRecord(User $user, string $id): ?object
    {
        /** @var TEntity|null $record */
        $record = $this->em->getRepository(ApiKey::class)->findOneBy([
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
            EntityGroupsInterface::GROUP_GENERAL,
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
            EntityGroupsInterface::GROUP_ID,
            EntityGroupsInterface::GROUP_GENERAL,
        ];

        return parent::toArray($record, $context);
    }
}
