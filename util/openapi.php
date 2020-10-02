<?php

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version=AZURACAST_VERSION,
 *     title="AzuraCast",
 *     description="AzuraCast is a standalone, turnkey web radio management tool. Radio stations hosted by AzuraCast expose a public API for viewing now playing data, making requests and more.",
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 *
 * @OA\Server(
 *     description=AZURACAST_API_NAME,
 *     url=AZURACAST_API_URL
 * )
 *
 * @OA\ExternalDocumentation(
 *     description="AzuraCast on GitHub",
 *     url="https://github.com/AzuraCast/AzuraCast"
 * )
 *
 * @OA\Parameter(
 *     parameter="station_id_required",
 *     name="station_id",
 *     description="The station ID",
 *     example=1,
 *     in="path",
 *     required=true,
 *     @OA\Schema(
 *         type="integer", format="int64"
 *     )
 * )
 *
 * @OA\Response(
 *     response="todo",
 *     description="This API call has no documented response (yet)",
 * )
 *
 * @OA\Tag(
 *     name="Now Playing",
 *     description="Endpoints that provide full summaries of the current state of stations.",
 * )
 *
 * @OA\Tag(name="Stations: General")
 * @OA\Tag(name="Stations: Song Requests")
 * @OA\Tag(name="Stations: Service Control")
 *
 * @OA\Tag(name="Stations: History")
 * @OA\Tag(name="Stations: Listeners")
 * @OA\Tag(name="Stations: Schedules")
 * @OA\Tag(name="Stations: Media")
 * @OA\Tag(name="Stations: Mount Points")
 * @OA\Tag(name="Stations: Playlists")
 * @OA\Tag(name="Stations: Queue")
 * @OA\Tag(name="Stations: Remote Relays")
 * @OA\Tag(name="Stations: Streamers/DJs")
 * @OA\Tag(name="Stations: Web Hooks")
 *
 * @OA\Tag(name="Administration: Custom Fields")
 * @OA\Tag(name="Administration: Users")
 * @OA\Tag(name="Administration: Relays")
 * @OA\Tag(name="Administration: Roles")
 * @OA\Tag(name="Administration: Settings")
 * @OA\Tag(name="Administration: Stations")
 *
 * @OA\Tag(name="Miscellaneous")
 *
 * @OA\SecurityScheme(
 *     type="apiKey",
 *     in="header",
 *     securityScheme="api_key",
 *     name="X-API-Key"
 * )
 */
