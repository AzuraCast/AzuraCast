<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Art;

use App\Entity;
use App\Exception\NoFileUploadedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Flow;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/station/{station_id}/art/{media_id}',
    description: 'Sets the album art for a track.',
    security: OpenApi::API_KEY_SECURITY,
    tags: ['Stations: Media'],
    parameters: [
        new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
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
        new OA\Response(
            response: 200,
            description: 'Success',
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
        ),
        new OA\Response(
            response: 404,
            description: 'Record not found',
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Error')
        ),
        new OA\Response(
            response: 403,
            description: 'Access denied',
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Error')
        ),
    ]
)]
class PostArtAction
{
    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param Entity\Repository\StationMediaRepository $mediaRepo
     * @param EntityManagerInterface $em
     * @param int|string $media_id
     *
     * @return ResponseInterface
     * @throws NoFileUploadedException
     */
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationMediaRepository $mediaRepo,
        EntityManagerInterface $em,
        $media_id
    ): ResponseInterface {
        $station = $request->getStation();

        $media = $mediaRepo->find($media_id, $station);
        if (!($media instanceof Entity\StationMedia)) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $mediaRepo->updateAlbumArt(
            $media,
            $flowResponse->readAndDeleteUploadedFile()
        );
        $em->flush();

        return $response->withJson(Entity\Api\Status::updated());
    }
}
