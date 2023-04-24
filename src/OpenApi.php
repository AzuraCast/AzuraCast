<?php

declare(strict_types=1);

namespace App;

use OpenApi\Attributes as OA;

#[
    OA\OpenApi(
        openapi: '3.0.0',
        info: new OA\Info(
            version: AZURACAST_VERSION,
            description: "AzuraCast is a standalone, turnkey web radio management tool. Radio stations hosted by"
            . " AzuraCast expose a public API for viewing now playing data, making requests and more.",
            title: 'AzuraCast',
            license: new OA\License(
                name: 'Apache 2.0',
                url: "https://www.apache.org/licenses/LICENSE-2.0.html"
            ),
        ),
        servers: [
            new OA\Server(
                url: AZURACAST_API_URL,
                description: AZURACAST_API_NAME
            ),
        ],
        tags: [
            new OA\Tag(
                name: "Now Playing",
                description: "Endpoints that provide full summaries of the current state of stations.",
            ),

            new OA\Tag(name: "Stations: General"),
            new OA\Tag(name: "Stations: Broadcasting"),
            new OA\Tag(name: "Stations: Song Requests"),
            new OA\Tag(name: "Stations: Service Control"),
            new OA\Tag(name: "Stations: Automation"),

            new OA\Tag(name: "Stations: History"),
            new OA\Tag(name: "Stations: HLS Streams"),
            new OA\Tag(name: "Stations: Listeners"),
            new OA\Tag(name: "Stations: Schedules"),
            new OA\Tag(name: "Stations: Media"),
            new OA\Tag(name: "Stations: Mount Points"),
            new OA\Tag(name: "Stations: Playlists"),
            new OA\Tag(name: "Stations: Podcasts"),
            new OA\Tag(name: "Stations: Queue"),
            new OA\Tag(name: "Stations: Remote Relays"),
            new OA\Tag(name: "Stations: SFTP Users"),
            new OA\Tag(name: "Stations: Streamers/DJs"),
            new OA\Tag(name: "Stations: Web Hooks"),

            new OA\Tag(name: "Administration: Custom Fields"),
            new OA\Tag(name: "Administration: Users"),
            new OA\Tag(name: "Administration: Relays"),
            new OA\Tag(name: "Administration: Roles"),
            new OA\Tag(name: "Administration: Settings"),
            new OA\Tag(name: "Administration: Stations"),
            new OA\Tag(name: "Administration: Storage Locations"),

            new OA\Tag(name: "Miscellaneous"),
        ],
        externalDocs: new OA\ExternalDocumentation(
            description: "AzuraCast on GitHub",
            url: "https://github.com/AzuraCast/AzuraCast"
        )
    ),
    OA\Parameter(
        parameter: "StationIdRequired",
        name: "station_id",
        in: "path",
        required: true,
        schema: new OA\Schema(
            anyOf: [
                new OA\Schema(type: "integer", format: "int64"),
                new OA\Schema(type: "string", format: "string"),
            ]
        ),
    ),
    OA\Response(
        response: 'Success',
        description: 'Success',
        content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
    ),
    OA\Response(
        response: 'AccessDenied',
        description: 'Access denied.',
        content: new OA\JsonContent(ref: '#/components/schemas/Api_Error')
    ),
    OA\Response(
        response: 'RecordNotFound',
        description: 'Record not found.',
        content: new OA\JsonContent(ref: '#/components/schemas/Api_Error')
    ),
    OA\Response(
        response: 'GenericError',
        description: 'A generic exception has occurred.',
        content: new OA\JsonContent(ref: '#/components/schemas/Api_Error')
    ),
    OA\SecurityScheme(
        securityScheme: "ApiKey",
        type: "apiKey",
        name: "X-API-Key",
        in: "header"
    )
]
final class OpenApi
{
    public const SAMPLE_TIMESTAMP = 1609480800;

    public const API_KEY_SECURITY = [['ApiKey' => []]];

    public const REF_STATION_ID_REQUIRED = '#/components/parameters/StationIdRequired';

    public const REF_RESPONSE_SUCCESS = '#/components/responses/Success';
    public const REF_RESPONSE_ACCESS_DENIED = '#/components/responses/AccessDenied';
    public const REF_RESPONSE_NOT_FOUND = '#/components/responses/RecordNotFound';
    public const REF_RESPONSE_GENERIC_ERROR = '#/components/responses/GenericError';
}
