<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Api\Traits\HasLinks;
use App\Entity\ApiKey;
use App\OpenApi;
use OpenApi\Attributes as OA;

/**
 * @extends AbstractApiCrudController<ApiKey>
 */
#[
    OA\Get(
        path: '/admin/api-keys',
        operationId: 'adminListApiKeys',
        description: 'List all current API keys across the system.',
        tags: ['Administration: General'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Delete(
        path: '/admin/api-key/{id}',
        operationId: 'adminDeleteApiKey',
        description: 'Delete a single API key.',
        tags: ['Administration: General'],
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
    protected string $resourceRouteName = 'api:admin:api-key';
}
