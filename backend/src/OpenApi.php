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
                name: 'GNU Affero General Public License v3.0 or later',
                identifier: 'AGPL-3.0-or-later',
                url: 'https://www.gnu.org/licenses/agpl-3.0.txt'
            ),
        ),
        servers: [
            new OA\Server(
                url: AZURACAST_API_URL,
                description: AZURACAST_API_NAME
            ),
        ],
        security: [
            ['ApiKey' => []],
        ],
        tags: [
            new OA\Tag(
                name: OpenApi::TAG_NOW_PLAYING,
                description: "Endpoints that provide full summaries of the current state of stations.",
            ),

            new OA\Tag(name: OpenApi::TAG_STATIONS),
            new OA\Tag(name: OpenApi::TAG_STATIONS_BROADCASTING),
            new OA\Tag(name: OpenApi::TAG_STATIONS_SONG_REQUESTS),
            new OA\Tag(name: OpenApi::TAG_STATIONS_SERVICE_CONTROL),
            new OA\Tag(name: OpenApi::TAG_STATIONS_HISTORY),
            new OA\Tag(name: OpenApi::TAG_STATIONS_HLS_STREAMS),
            new OA\Tag(name: OpenApi::TAG_STATIONS_LISTENERS),
            new OA\Tag(name: OpenApi::TAG_STATIONS_SCHEDULES),
            new OA\Tag(name: OpenApi::TAG_STATIONS_MEDIA),
            new OA\Tag(name: OpenApi::TAG_STATIONS_MOUNT_POINTS),
            new OA\Tag(name: OpenApi::TAG_STATIONS_PLAYLISTS),
            new OA\Tag(name: OpenApi::TAG_STATIONS_PODCASTS),
            new OA\Tag(name: OpenApi::TAG_STATIONS_QUEUE),
            new OA\Tag(name: OpenApi::TAG_STATIONS_REMOTE_RELAYS),
            new OA\Tag(name: OpenApi::TAG_STATIONS_SFTP_USERS),
            new OA\Tag(name: OpenApi::TAG_STATIONS_STREAMERS),
            new OA\Tag(name: OpenApi::TAG_STATIONS_WEBHOOKS),

            new OA\Tag(name: OpenApi::TAG_ADMIN),
            new OA\Tag(name: OpenApi::TAG_ADMIN_CUSTOM_FIELDS),
            new OA\Tag(name: OpenApi::TAG_ADMIN_USERS),
            new OA\Tag(name: OpenApi::TAG_ADMIN_ROLES),
            new OA\Tag(name: OpenApi::TAG_ADMIN_SETTINGS),
            new OA\Tag(name: OpenApi::TAG_ADMIN_STATIONS),
            new OA\Tag(name: OpenApi::TAG_ADMIN_STORAGE_LOCATIONS),

            new OA\Tag(name: OpenApi::TAG_ACCOUNTS),
            new OA\Tag(name: OpenApi::TAG_MISC),
        ],
        externalDocs: new OA\ExternalDocumentation(
            description: "AzuraCast on GitHub",
            url: "https://github.com/AzuraCast/AzuraCast"
        ),
        x: [
            'tagGroups' => [
                [
                    'name' => 'Station Management',
                    'tags' => self::TAG_GROUP_STATIONS,
                ],
                [
                    'name' => 'Administration',
                    'tags' => self::TAG_GROUP_ADMIN,
                ],
            ],
        ]
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
    OA\RequestBody(
        request: 'FlowFileUpload',
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                properties: [
                    new OA\Property(
                        property: 'file',
                        description: 'The body of the file to upload.',
                        type: 'string',
                        format: 'binary'
                    ),
                ]
            )
        )
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
    public const string TAG_NOW_PLAYING = 'Now Playing';

    public const string TAG_STATIONS = 'Stations: General';
    public const string TAG_STATIONS_BROADCASTING = 'Stations: Broadcasting';
    public const string TAG_STATIONS_SONG_REQUESTS = 'Stations: Song Requests';
    public const string TAG_STATIONS_SERVICE_CONTROL = 'Stations: Service Control';

    public const string TAG_STATIONS_HISTORY = 'Stations: History';
    public const string TAG_STATIONS_HLS_STREAMS = 'Stations: HLS Streams';
    public const string TAG_STATIONS_LISTENERS = 'Stations: Listeners';
    public const string TAG_STATIONS_SCHEDULES = 'Stations: Schedules';
    public const string TAG_STATIONS_MEDIA = 'Stations: Media';
    public const string TAG_STATIONS_MOUNT_POINTS = 'Stations: Mount Points';
    public const string TAG_STATIONS_PLAYLISTS = 'Stations: Playlists';
    public const string TAG_STATIONS_PODCASTS = 'Stations: Podcasts';
    public const string TAG_STATIONS_QUEUE = 'Stations: Queue';
    public const string TAG_STATIONS_REMOTE_RELAYS = 'Stations: Remote Relays';
    public const string TAG_STATIONS_SFTP_USERS = 'Stations: SFTP Users';
    public const string TAG_STATIONS_STREAMERS = 'Stations: Streamers/DJs';
    public const string TAG_STATIONS_WEBHOOKS = 'Stations: Web Hooks';

    public const array TAG_GROUP_STATIONS = [
        self::TAG_STATIONS,
        self::TAG_STATIONS_BROADCASTING,
        self::TAG_STATIONS_SONG_REQUESTS,
        self::TAG_STATIONS_SERVICE_CONTROL,
        self::TAG_STATIONS_HISTORY,
        self::TAG_STATIONS_HLS_STREAMS,
        self::TAG_STATIONS_LISTENERS,
        self::TAG_STATIONS_SCHEDULES,
        self::TAG_STATIONS_MEDIA,
        self::TAG_STATIONS_MOUNT_POINTS,
        self::TAG_STATIONS_PLAYLISTS,
        self::TAG_STATIONS_PODCASTS,
        self::TAG_STATIONS_QUEUE,
        self::TAG_STATIONS_REMOTE_RELAYS,
        self::TAG_STATIONS_SFTP_USERS,
        self::TAG_STATIONS_STREAMERS,
        self::TAG_STATIONS_WEBHOOKS,
    ];

    public const string TAG_ADMIN = 'Administration: General';
    public const string TAG_ADMIN_CUSTOM_FIELDS = 'Administration: Custom Fields';
    public const string TAG_ADMIN_USERS = 'Administration: Users';
    public const string TAG_ADMIN_ROLES = 'Administration: Roles';
    public const string TAG_ADMIN_SETTINGS = 'Administration: Settings';
    public const string TAG_ADMIN_STATIONS = 'Administration: Stations';
    public const string TAG_ADMIN_STORAGE_LOCATIONS = 'Administration: Storage Locations';

    public const array TAG_GROUP_ADMIN = [
        self::TAG_ADMIN,
        self::TAG_ADMIN_CUSTOM_FIELDS,
        self::TAG_ADMIN_USERS,
        self::TAG_ADMIN_ROLES,
        self::TAG_ADMIN_SETTINGS,
        self::TAG_ADMIN_STATIONS,
        self::TAG_ADMIN_STORAGE_LOCATIONS,
    ];

    public const string TAG_ACCOUNTS = 'Accounts';

    public const string TAG_MISC = 'Miscellaneous';

    public const int SAMPLE_TIMESTAMP = 1609480800;
    public const string SAMPLE_DATETIME = '2025-01-31T21:31:58+00:00';

    public const string REF_STATION_ID_REQUIRED = '#/components/parameters/StationIdRequired';

    public const string REF_RESPONSE_SUCCESS = '#/components/responses/Success';
    public const string REF_RESPONSE_ACCESS_DENIED = '#/components/responses/AccessDenied';
    public const string REF_RESPONSE_NOT_FOUND = '#/components/responses/RecordNotFound';
    public const string REF_RESPONSE_GENERIC_ERROR = '#/components/responses/GenericError';

    public const string REF_REQUEST_BODY_FLOW_FILE_UPLOAD = '#/components/requestBodies/FlowFileUpload';
}
