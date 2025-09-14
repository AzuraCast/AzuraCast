<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Entity\UserPasskey;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @template TEntity as UserPasskey
 * @extends AbstractApiCrudController<TEntity>
 */
#[
    OA\Get(
        path: '/frontend/account/passkeys',
        operationId: 'getAccountListPasskeys',
        summary: 'List currently registered passkeys.',
        tags: [OpenApi::TAG_ACCOUNTS],
        responses: [
            // TODO API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/frontend/account/passkey/{id}',
        operationId: 'getAccountGetPasskey',
        summary: 'Get the details of a single passkey.',
        tags: [OpenApi::TAG_ACCOUNTS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Passkey ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            // TODO API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Delete(
        path: '/frontend/account/passkey/{id}',
        operationId: 'deleteAccountPasskey',
        summary: 'Delete a specified passkey by ID.',
        tags: [OpenApi::TAG_ACCOUNTS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Passkey ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            // TODO API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
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
