<?php
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
 *     description="AzuraCast Demo API",
 *     url="https://demo.azuracast.com/api"
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
 *         type="int64"
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
 * @OA\Tag(name="Miscellaneous")
 * @OA\Tag(name="Stations: General")
 * @OA\Tag(name="Stations: Song Requests")
 * @OA\Tag(name="Stations: Listeners")
 *
 * @OA\SecurityScheme(
 *     type="apiKey",
 *     in="header",
 *     securityScheme="api_key",
 *     name="X-API-Key"
 * )
 */
