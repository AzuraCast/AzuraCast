<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
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

final class FlowUploadAction
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly MediaProcessor $mediaProcessor,
        private readonly StationPlaylistMediaRepository $spmRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
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

        // If the user is looking at a playlist's contents, add uploaded media to that playlist.
        if ($stationMedia instanceof StationMedia && !empty($allParams['searchPhrase'])) {
            $search_phrase = $allParams['searchPhrase'];

            if (str_starts_with($search_phrase, 'playlist:')) {
                $playlist_name = substr($search_phrase, 9);

                $playlist = $this->em->getRepository(StationPlaylist::class)->findOneBy(
                    [
                        'station_id' => $station->getId(),
                        'name' => $playlist_name,
                    ]
                );

                if ($playlist instanceof StationPlaylist) {
                    $this->spmRepo->addMediaToPlaylist($stationMedia, $playlist);
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
