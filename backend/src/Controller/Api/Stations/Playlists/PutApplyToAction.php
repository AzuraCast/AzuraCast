<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationPlaylistFolderRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Put(
    path: '/station/{station_id}/playlist/{id}/apply-to',
    operationId: 'putStationPlaylistApplyTo',
    summary: 'Apply the specified playlist to the specified directories.',
    tags: [OpenApi::TAG_STATIONS_PLAYLISTS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'id',
            description: 'Playlist ID',
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
final class PutApplyToAction extends AbstractClonableAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationPlaylistFolderRepository $folderRepo,
        StationPlaylistRepository $playlistRepo
    ) {
        parent::__construct($playlistRepo);
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        $station = $request->getStation();
        $record = $this->playlistRepo->requireForStation($id, $station);

        $data = (array)$request->getParsedBody();

        $clone = $data['copyPlaylist'] ?? false;
        $directories = (array)($data['directories'] ?? []);

        foreach ($directories as $directory) {
            if ($clone) {
                $playlist = $this->clone(
                    $record,
                    $record->name . ' - ' . $directory
                );
            } else {
                $playlist = $record;
            }

            $this->folderRepo->addPlaylistsToFolder(
                $station,
                $directory,
                [
                    $playlist->id => 0,
                ]
            );
        }

        return $response->withJson(
            new Status(
                true,
                __('Playlist applied to folders.')
            )
        );
    }
}
