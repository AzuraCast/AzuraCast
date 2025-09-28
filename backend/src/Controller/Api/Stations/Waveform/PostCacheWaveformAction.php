<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Waveform;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationMediaRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/station/{station_id}/waveform/{media_id}',
    operationId: 'postStationMediaWaveform',
    summary: 'Save cached waveform data for a media ID (for the Visual Cue Editor).',
    tags: [OpenApi::TAG_STATIONS_MEDIA],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'id',
            description: 'Media ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', format: 'int64')
        ),
    ],
    responses: [
        // TODO API Response
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class PostCacheWaveformAction implements SingleActionInterface
{
    public function __construct(
        private StationMediaRepository $mediaRepo
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

        $media = $this->mediaRepo->requireByUniqueId($mediaId, $station);

        $waveformData = Types::arrayOrNull($request->getParsedBody());
        if (empty($waveformData) || empty($waveformData['data'])) {
            throw new InvalidArgumentException('No waveform data provided.');
        }

        $this->mediaRepo->saveWaveformData($media, $waveformData);

        return $response->withJson(Status::updated());
    }
}
