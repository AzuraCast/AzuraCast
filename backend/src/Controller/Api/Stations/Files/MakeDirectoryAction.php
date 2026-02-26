<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Cache\MediaListCache;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use League\Flysystem\UnableToCreateDirectory;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Post(
        path: '/station/{station_id}/files/mkdir',
        operationId: 'postStationFilesMkdir',
        summary: 'Create a directory in a station media directory.',
        tags: [OpenApi::TAG_STATIONS_MEDIA],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            // TODO: API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final readonly class MakeDirectoryAction implements SingleActionInterface
{
    public function __construct(
        private MediaListCache $mediaListCache,
        private StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $currentDir = Types::string($request->getParam('currentDirectory'));
        $newDirName = Types::string($request->getParam('name'));

        if (empty($newDirName)) {
            return $response->withStatus(400)
                ->withJson(new Error(400, __('No directory specified')));
        }

        $station = $request->getStation();

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        $newDir = $currentDir . '/' . $newDirName;

        try {
            $fsMedia->createDirectory($newDir);
        } catch (UnableToCreateDirectory $e) {
            return $response->withStatus(400)
                ->withJson(new Error(400, $e->getMessage()));
        }

        $this->mediaListCache->clearCache($station);

        return $response->withJson(Status::created());
    }
}
