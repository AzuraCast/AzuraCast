<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Entity\UserPasskey;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @template TEntity as UserPasskey
 * @extends AbstractApiCrudController<TEntity>
 */
final class PasskeysController extends AbstractApiCrudController
{
    protected string $entityClass = UserPasskey::class;
    protected string $resourceRouteName = 'api:frontend:passkey';

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $query = $this->em->createQuery(
            <<<'DQL'
            SELECT e FROM App\Entity\UserPasskey e WHERE e.user = :user
        DQL
        )->setParameter('user', $request->getUser());

        return $this->listPaginatedFromQuery($request, $response, $query);
    }

    public function createAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        throw new RuntimeException('Not implemented. See /frontend/account/webauthn/register.');
    }

    public function editAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        throw new RuntimeException('Not implemented.');
    }

    /**
     * @return UserPasskey|null
     */
    protected function getRecord(ServerRequest $request, array $params): ?object
    {
        /** @var string $id */
        $id = $params['id'];

        /** @var UserPasskey|null $record */
        $record = $this->em->getRepository(UserPasskey::class)->findOneBy([
            'id' => $id,
            'user' => $request->getUser(),
        ]);
        return $record;
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
