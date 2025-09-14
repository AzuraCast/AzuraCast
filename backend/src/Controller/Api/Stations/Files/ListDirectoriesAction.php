<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Controller\SingleActionInterface;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use League\Flysystem\StorageAttributes;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/station/{station_id}/files/directories',
        operationId: 'getStationFileDirectories',
        summary: 'List directories in a station media library for moving/renaming.',
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
final readonly class ListDirectoriesAction implements SingleActionInterface
{
    public function __construct(
        private StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $currentDir = Types::string($request->getParam('currentDirectory', ''));

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        $directoriesRaw = $fsMedia->listContents($currentDir, false)->filter(
            fn(StorageAttributes $attrs) => $attrs->isDir()
                && !StationFilesystems::isDotFile($attrs->path())
        )->sortByPath();

        $directories = [];
        foreach ($directoriesRaw as $directory) {
            /** @var StorageAttributes $directory */
            $path = $directory->path();

            $directories[] = [
                'name' => basename($path),
                'path' => $path,
            ];
        }

        return $response->withJson(
            [
                'rows' => $directories,
            ]
        );
    }
}
