<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity\SftpUser;
use App\OpenApi;
use OpenApi\Attributes as OA;

/** @extends AbstractStationApiCrudController<SftpUser> */
#[
    OA\Get(
        path: '/station/{station_id}/sftp-users',
        operationId: 'getSftpUsers',
        description: 'List all current SFTP users.',
        tags: [OpenApi::TAG_STATIONS_SFTP_USERS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: SftpUser::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/sftp-users',
        operationId: 'addSftpUser',
        description: 'Create a new SFTP user.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: SftpUser::class)
        ),
        tags: [OpenApi::TAG_STATIONS_SFTP_USERS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: SftpUser::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/sftp-user/{id}',
        operationId: 'getSftpUser',
        description: 'Retrieve details for a single SFTP user.',
        tags: [OpenApi::TAG_STATIONS_SFTP_USERS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'SFTP User ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: SftpUser::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/sftp-user/{id}',
        operationId: 'editSftpUser',
        description: 'Update details of a single SFTP user.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: SftpUser::class)
        ),
        tags: [OpenApi::TAG_STATIONS_SFTP_USERS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'SFTP User ID',
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
        path: '/station/{station_id}/sftp-user/{id}',
        operationId: 'deleteSftpUser',
        description: 'Delete a single SFTP user.',
        tags: [OpenApi::TAG_STATIONS_SFTP_USERS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'SFTP User ID',
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
final class SftpUsersController extends AbstractStationApiCrudController
{
    protected string $entityClass = SftpUser::class;
    protected string $resourceRouteName = 'api:stations:sftp-user';
}
