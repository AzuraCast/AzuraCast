<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Art;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/station/{station_id}/art/{media_id}',
    description: 'Removes the album art for a track.',
    security: OpenApi::API_KEY_SECURITY,
    tags: ['Stations: Media'],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'id',
            description: 'Media ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(
                anyOf: [
                    new OA\Schema(type: 'integer', format: 'int64'),
                    new OA\Schema(type: 'string'),
                ]
            )
        ),
    ],
    responses: [
        new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
final class DeleteArtAction
{
    public function __construct(
        private readonly Entity\Repository\StationMediaRepository $mediaRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $media_id
    ): ResponseInterface {
        $station = $request->getStation();

        $media = $this->mediaRepo->requireForStation($media_id, $station);
        $this->mediaRepo->removeAlbumArt($media);

        return $response->withJson(Entity\Api\Status::deleted());
    }
}
