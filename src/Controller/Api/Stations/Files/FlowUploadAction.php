<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

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
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;

final class FlowUploadAction implements SingleActionInterface
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;
    use HasMediaSearch;

    public function __construct(
        private readonly MediaProcessor $mediaProcessor,
        private readonly StationPlaylistMediaRepository $spmRepo,
        private readonly StationPlaylistFolderRepository $spfRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $allParams = $request->getParams();
        $station = $request->getStation();

        $mediaStorage = $station->getMediaStorageLocation();
        $mediaStorage->errorIfFull();

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $currentDir = $request->getParam('currentDirectory', '');

        $destPath = $flowResponse->getClientFilename();
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
                [$searchPhrase, $playlist] = $this->parseSearchQuery(
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

        return $response->withJson(Status::created());
    }
}
