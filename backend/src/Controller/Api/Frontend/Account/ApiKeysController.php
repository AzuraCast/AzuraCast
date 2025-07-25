<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Api\Account\NewApiKey;
use App\Entity\Api\Traits\HasLinks;
use App\Entity\ApiKey;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Security\SplitToken;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @template TEntity as ApiKey
 * @extends AbstractApiCrudController<ApiKey>
 */
#[
    OA\Get(
        path: '/frontend/account/api-keys',
        operationId: 'getMyApiKeys',
        summary: 'List all API keys associated with your account.',
        tags: [OpenApi::TAG_ACCOUNTS],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        allOf: [
                            new OA\Schema(ref: ApiKey::class),
                            new OA\Schema(ref: HasLinks::class),
                        ]
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/frontend/account/api-keys',
        operationId: 'addMyApiKey',
        summary: 'Create a new API key associated with your account.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: ApiKey::class)
        ),
        tags: [OpenApi::TAG_ACCOUNTS],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: NewApiKey::class),
                        new OA\Schema(ref: ApiKey::class),
                        new OA\Schema(ref: HasLinks::class),
                    ]
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/frontend/account/api-key/{id}',
        operationId: 'getMyApiKey',
        summary: 'Retrieve details for a single API key.',
        tags: [OpenApi::TAG_ACCOUNTS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'API Key ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: ApiKey::class),
                        new OA\Schema(ref: HasLinks::class),
                    ]
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Delete(
        path: '/frontend/account/api-key/{id}',
        operationId: 'deleteMyApiKey',
        summary: 'Delete a single API key associated with your account.',
        tags: [OpenApi::TAG_ACCOUNTS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'API Key ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class ApiKeysController extends AbstractApiCrudController
{
    protected string $entityClass = ApiKey::class;
    protected string $resourceRouteName = 'api:frontend:api-key';

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
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
        Response $response,
        array $params
    ): ResponseInterface {
        $newKey = SplitToken::generate();

        $parsedBody = (array)$request->getParsedBody();

        $record = new ApiKey(
            $request->getUser(),
            $newKey,
            Types::string($parsedBody['comment'] ?? null)
        );

        $this->em->persist($record);
        $this->em->flush();

        $return = $this->viewRecord($record, $request);
        $return['key'] = (string)$newKey;

        return $response->withJson($return);
    }

    /**
     * @return TEntity|null
     */
    protected function getRecord(ServerRequest $request, array $params): ?object
    {
        /** @var string $id */
        $id = $params['id'];

        /** @var TEntity|null $record */
        $record = $this->em->getRepository(ApiKey::class)->findOneBy([
            'id' => $id,
            'user' => $request->getUser(),
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
