<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\CustomField;
use App\OpenApi;
use OpenApi\Attributes as OA;

/** @extends AbstractApiCrudController<CustomField> */
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/admin/custom_fields',
        operationId: 'addCustomField',
        description: 'Create a new custom field.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/CustomField')
        ),
        tags: ['Administration: Custom Fields'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/CustomField')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class CustomFieldsController extends AbstractApiCrudController
{
    protected string $entityClass = CustomField::class;
    protected string $resourceRouteName = 'api:admin:custom_field';
}
