<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Cache\MediaListCache;
use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Controller\Api\Traits\HasMediaSearch;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Entity\Repository\StationPlaylistFolderRepository;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Exception\CannotProcessMediaException;
use App\Exception\StorageLocationFullException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\MediaProcessor;
use App\OpenApi;
use App\Service\Flow;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Post(
        path: '/station/{station_id}/files/upload',
        operationId: 'postUploadFile',
        summary: 'Upload and process a new media file.',
        requestBody: new OA\RequestBody(
            ref: OpenApi::REF_REQUEST_BODY_FLOW_FILE_UPLOAD
        ),
        tags: [OpenApi::TAG_STATIONS_MEDIA],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class FlowUploadAction implements SingleActionInterface
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;
    use HasMediaSearch;

    public function __construct(
        private readonly MediaProcessor $mediaProcessor,
        private readonly StationPlaylistMediaRepository $spmRepo,
        private readonly StationPlaylistFolderRepository $spfRepo,
        private readonly MediaListCache $mediaListCache
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $allParams = $request->getParams();
        $station = $request->getStation();

        $mediaStorage = $station->media_storage_location;
        $mediaStorage->errorIfFull();

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $currentDir = Types::string($request->getParam('currentDirectory'));

        $destPath = $flowResponse->getClientFullPath();
        if (!empty($currentDir)) {
            $destPath = $currentDir . '/' . $destPath;
        }

        $uploadedSize = $flowResponse->getSize();

        if (!$mediaStorage->canHoldFile($uploadedSize)) {
            throw new StorageLocationFullException();
        }

        try {
            $tempPath = $flowResponse->getUploadedPath();

            $stationMedia = $this->mediaProcessor->processAndUpload(
                $mediaStorage,
                $destPath,
                $tempPath
            );
        } catch (CannotProcessMediaException $e) {
            $this->logger->error(
                $e->getMessageWithPath(),
                [
                    'exception' => $e,
                ]
            );

            return $response->withJson(Error::fromException($e));
        }

        if ($stationMedia instanceof StationMedia) {
            if (!empty($allParams['searchPhrase'])) {
                // If the user is looking at a playlist's contents, add uploaded media to that playlist.
                [, $playlist] = $this->parseSearchQuery(
                    $station,
                    $allParams['searchPhrase']
                );

                if (null !== $playlist) {
                    $this->spmRepo->addMediaToPlaylist($stationMedia, $playlist);
                    $this->em->flush();
                }
            } elseif (!empty($currentDir)) {
                // If the user is viewing a regular directory, check for playlists assigned to the directory and assign
                // them to this media immediately.
                $playlistIds = $this->spfRepo->getPlaylistIdsForFolderAndParents($station, $currentDir);

                if (!empty($playlistIds)) {
                    foreach ($playlistIds as $playlistId) {
                        $playlist = $this->em->find(StationPlaylist::class, $playlistId);
                        if (null !== $playlist) {
                            $this->spmRepo->addMediaToPlaylist($stationMedia, $playlist);
                        }
                    }
                    $this->em->flush();
                }
            }
        }

        $mediaStorage->addStorageUsed($uploadedSize);
        $this->em->persist($mediaStorage);
        $this->em->flush();

        $this->mediaListCache->clearCache($mediaStorage);

        return $response->withJson(Status::created());
    }
}
