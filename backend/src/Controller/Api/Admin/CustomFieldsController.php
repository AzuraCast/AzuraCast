<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Api\Traits\HasLinks;
use App\Entity\CustomField;
use App\OpenApi;
use OpenApi\Attributes as OA;

/** @extends AbstractApiCrudController<CustomField> */
#[
    OA\Get(
        path: '/admin/custom_fields',
        operationId: 'getCustomFields',
        summary: 'List all current custom fields in the system.',
        tags: [OpenApi::TAG_ADMIN_CUSTOM_FIELDS],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        allOf: [
                            new OA\Schema(ref: CustomField::class),
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
        path: '/admin/custom_fields',
        operationId: 'addCustomField',
        summary: 'Create a new custom field.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: CustomField::class)
        ),
        tags: [OpenApi::TAG_ADMIN_CUSTOM_FIELDS],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: CustomField::class),
                        new OA\Schema(ref: HasLinks::class),
                    ]
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/admin/custom_field/{id}',
        operationId: 'getCustomField',
        summary: 'Retrieve details for a single custom field.',
        tags: [OpenApi::TAG_ADMIN_CUSTOM_FIELDS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Custom Field ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: CustomField::class),
                        new OA\Schema(ref: HasLinks::class),
                    ]
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/admin/custom_field/{id}',
        operationId: 'editCustomField',
        summary: 'Update details of a single custom field.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: CustomField::class)
        ),
        tags: [OpenApi::TAG_ADMIN_CUSTOM_FIELDS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Custom Field ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Delete(
        path: '/admin/custom_field/{id}',
        operationId: 'deleteCustomField',
        summary: 'Delete a single custom field.',
        tags: [OpenApi::TAG_ADMIN_CUSTOM_FIELDS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Custom Field ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
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
final class CustomFieldsController extends AbstractApiCrudController
{
    protected string $entityClass = CustomField::class;
    protected string $resourceRouteName = 'api:admin:custom_field';
}
