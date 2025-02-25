<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\ApiKey;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Security\SplitToken;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @template TEntity as ApiKey
 * @extends AbstractApiCrudController<TEntity>
 */
#[
    OA\Get(
        path: '/frontend/account/api-keys',
        operationId: 'getMyApiKeys',
        description: 'List all API keys associated with your account.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Accounts'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        allOf: [
                            new OA\Schema(ref: '#/components/schemas/ApiKey'),
                            new OA\Schema(ref: '#/components/schemas/HasLinks'),
                        ]
                    )
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/frontend/account/api-keys',
        operationId: 'addMyApiKey',
        description: 'Create a new API key associated with your account.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/ApiKey')
        ),
        tags: ['Accounts'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/Api_Account_NewApiKey'),
                        new OA\Schema(ref: '#/components/schemas/ApiKey'),
                        new OA\Schema(ref: '#/components/schemas/HasLinks'),
                    ]
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Get(
        path: '/frontend/account/api-key/{id}',
        operationId: 'getMyApiKey',
        description: 'Retrieve details for a single API key.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Accounts'],
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
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/ApiKey'),
                        new OA\Schema(ref: '#/components/schemas/HasLinks'),
                    ]
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Delete(
        path: '/frontend/account/api-key/{id}',
        operationId: 'deleteMyApiKey',
        description: 'Delete a single API key associated with your account.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Accounts'],
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
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
