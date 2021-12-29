<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity;
use App\OpenApi;
use OpenApi\Attributes as OA;

/** @extends AbstractAdminApiCrudController<Entity\CustomField> */
#[
    OA\Get(
        path: '/admin/custom_fields',
        operationId: 'getCustomFields',
        description: 'List all current custom fields in the system.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Custom Fields'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/CustomField'))
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Post(
        path: '/admin/custom_fields',
        operationId: 'addCustomField',
        description: 'Create a new custom field.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Custom Fields'],
        responses: [
            new OA\RequestBody(
                content: new OA\JsonContent(ref: '#/components/schemas/CustomField')
            ),
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/CustomField')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Get(
        path: '/admin/custom_field/{id}',
        operationId: 'getCustomField',
        description: 'Retrieve details for a single custom field.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Custom Fields'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/CustomField')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Put(
        path: '/admin/custom_field/{id}',
        operationId: 'editCustomField',
        description: 'Update details of a single custom field.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/CustomField')
        ),
        tags: ['Administration: Custom Fields'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Delete(
        path: '/admin/custom_field/{id}',
        operationId: 'deleteCustomField',
        description: 'Delete a single custom field.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Custom Fields'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    )
]
class CustomFieldsController extends AbstractAdminApiCrudController
{
    protected string $entityClass = Entity\CustomField::class;
    protected string $resourceRouteName = 'api:admin:custom_field';
}
