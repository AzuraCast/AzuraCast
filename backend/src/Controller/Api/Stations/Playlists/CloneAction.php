<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/station/{station_id}/playlist/{id}/clone',
    operationId: 'postStationPlaylistClone',
    summary: 'Create a copy of the specified playlist.',
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'name',
                    description: 'The name of the newly cloned playlist.',
                    type: 'string'
                ),
                new OA\Property(
                    property: 'clone',
                    description: 'Which parts of the original playlist to clone.',
                    type: 'array',
                    items: new OA\Items(
                        type: 'string',
                        enum: [
                            'schedule',
                            'media',
                        ]
                    ),
                ),
            ]
        )
    ),
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
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class CloneAction extends AbstractClonableAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        $record = $this->playlistRepo->requireForStation($id, $request->getStation());

        $data = (array)$request->getParsedBody();
        $toClone = $data['clone'] ?? [];

        $this->clone(
            $record,
            $data['name'],
            in_array('schedule', $toClone, true),
            in_array('media', $toClone, true)
        );

        $this->em->flush();

        return $response->withJson(Status::created());
    }
}
