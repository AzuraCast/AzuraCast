<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Art;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationMediaRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Flow;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/station/{station_id}/art/{media_id}',
    operationId: 'postMediaArt',
    summary: 'Sets the album art for a track.',
    requestBody: new OA\RequestBody(ref: OpenApi::REF_REQUEST_BODY_FLOW_FILE_UPLOAD),
    tags: [OpenApi::TAG_STATIONS_MEDIA],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'media_id',
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
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class PostArtAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StationMediaRepository $mediaRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $mediaId */
        $mediaId = $params['media_id'];

        $station = $request->getStation();

        $media = $this->mediaRepo->requireForStation($mediaId, $station);

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $this->mediaRepo->updateAlbumArt(
            $media,
            $flowResponse->readAndDeleteUploadedFile()
        );
        $this->em->flush();

        return $response->withJson(Status::updated());
    }
}
