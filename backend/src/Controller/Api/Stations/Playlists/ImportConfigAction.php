<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\PlaylistConfiguration\PlaylistConfigurationImportResult;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\PlaylistConfiguration\PlaylistConfigurationImporter;
use App\Utilities\Types;
use JsonException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Throwable;

use const JSON_THROW_ON_ERROR;

#[OA\Post(
    path: '/station/{station_id}/playlists/import-config',
    operationId: 'postStationPlaylistsImportConfig',
    summary: 'Import playlists from a JSON configuration dump, creating new playlists on the station.',
    requestBody: new OA\RequestBody(
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                properties: [
                    new OA\Property(
                        property: 'config_file',
                        description: 'The JSON configuration dump to import.',
                        type: 'string',
                        format: 'binary'
                    ),
                    new OA\Property(
                        property: 'name_prefix',
                        description: 'Optional prefix prepended to every imported playlist name.',
                        type: 'string',
                        nullable: true
                    ),
                ]
            )
        )
    ),
    tags: [OpenApi::TAG_STATIONS_PLAYLISTS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
    ],
    responses: [
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class ImportConfigAction implements SingleActionInterface
{
    public function __construct(
        private readonly PlaylistConfigurationImporter $importer
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $files = $request->getUploadedFiles();

        if (empty($files['config_file'])) {
            return $response->withStatus(400)
                ->withJson(new Error(400, 'No "config_file" provided.'));
        }

        /** @var UploadedFileInterface $file */
        $file = $files['config_file'];

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return $response->withStatus(400)
                ->withJson(Error::fromFileError($file->getError()));
        }

        try {
            $dump = json_decode(
                $file->getStream()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            return $response->withStatus(400)
                ->withJson(new Error(400, 'Invalid JSON: ' . $exception->getMessage()));
        }

        if (!is_array($dump)) {
            return $response->withStatus(400)
                ->withJson(new Error(400, 'Invalid configuration dump.'));
        }

        $namePrefix = Types::stringOrNull($request->getParam('name_prefix'), true);

        try {
            $summary = $this->importer->import(
                $dump,
                $request->getStation(),
                $namePrefix
            );
        } catch (Throwable $exception) {
            return $response->withStatus(400)
                ->withJson(new Error(400, $exception->getMessage()));
        }

        return $response->withJson(
            new PlaylistConfigurationImportResult(
                success: true,
                message: sprintf(
                    __('Imported %d playlist(s) successfully.'),
                    $summary->playlistsCreated
                ),
                playlistsCreated: $summary->playlistsCreated,
                mediaRelinked: $summary->mediaRelinked,
                mediaGenerated: $summary->mediaGenerated,
                membersCreated: $summary->membersCreated,
                warnings: $summary->warnings
            )
        );
    }
}
