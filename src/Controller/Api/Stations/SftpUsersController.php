<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\OpenApi;
use OpenApi\Attributes as OA;

/** @extends AbstractStationApiCrudController<Entity\SftpUser> */
#[
    OA\Get(
        path: '/station/{station_id}/sftp-users',
        operationId: 'getSftpUsers',
        description: 'List all current SFTP users.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: SFTP Users'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SftpUser')
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/sftp-users',
        operationId: 'addSftpUser',
        description: 'Create a new SFTP user.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/SftpUser')
        ),
        tags: ['Stations: SFTP Users'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SftpUser')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/sftp-user/{id}',
        operationId: 'getSftpUser',
        description: 'Retrieve details for a single SFTP user.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: SFTP Users'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'SFTP User ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SftpUser')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/sftp-user/{id}',
        operationId: 'editSftpUser',
        description: 'Update details of a single SFTP user.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/SftpUser')
        ),
        tags: ['Stations: SFTP Users'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Remote Relay ID',
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
        path: '/station/{station_id}/sftp-user/{id}',
        operationId: 'deleteSftpUser',
        description: 'Delete a single remote relay.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: SFTP Users'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Remote Relay ID',
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
class SftpUsersController extends AbstractStationApiCrudController
{
    protected string $entityClass = Entity\SftpUser::class;
    protected string $resourceRouteName = 'api:stations:sftp-user';
}
