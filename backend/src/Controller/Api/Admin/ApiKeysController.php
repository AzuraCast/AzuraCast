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
        summary: 'List all current API keys across the system.',
        tags: [OpenApi::TAG_ADMIN],
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
    OA\Delete(
        path: '/admin/api-key/{id}',
        operationId: 'adminDeleteApiKey',
        summary: 'Delete a single API key.',
        tags: [OpenApi::TAG_ADMIN],
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
    protected string $resourceRouteName = 'api:admin:api-key';
}
