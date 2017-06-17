<?php
/**
 * @SWG\Swagger(
 *     schemes={"http","https"},
 *     consumes={"application/json"},
 *     produces={"application/json"},
 *     host="",
 *     basePath="/api",
 *     @SWG\Info(
 *         version=AZURACAST_VERSION,
 *         title="AzuraCast",
 *         description="AzuraCast is a standalone, turnkey web radio management tool. Radio stations hosted by AzuraCast expose a public API for viewing now playing data, making requests and more.",
 *         @SWG\License(
 *             name="Apache 2.0",
 *             url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *         )
 *     ),
 *     @SWG\ExternalDocumentation(
 *         description="AzuraCast on GitHub",
 *         url="https://github.com/AzuraCast/AzuraCast"
 *     )
 * )
 *
 * @SWG\Parameter(
 *   parameter="station_id_required",
 *   name="station_id",
 *   description="The station ID",
 *   type="integer",
 *   format="int64",
 *   in="path",
 *   required=true
 * )
 *
 * @SWG\Response(
 *   response="todo",
 *   description="This API call has no documentated response (yet)",
 * )
 *
 * @SWG\SecurityScheme(
 *   securityDefinition="api_key",
 *   type="apiKey",
 *   in="header",
 *   name="X-API-Key"
 * )
 *
 * @SWG\Tag(
 *   name="Now Playing",
 *   description="Endpoints that provide full summaries of the current state of stations.",
 * )
 * @SWG\Tag(
 *   name="Station Details",
 *   description="Specific information on individual stations"
 * )
 * @SWG\Tag(
 *   name="Authenticated Endpoints",
 *   description="API calls with more detailed information, requiring specific credentialed access.",
 * )
 */