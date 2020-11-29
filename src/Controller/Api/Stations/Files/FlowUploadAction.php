<?php

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Exception\CannotProcessMediaException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class FlowUploadAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Entity\Repository\StationPlaylistMediaRepository $spmRepo,
        LoggerInterface $logger
    ): ResponseInterface {
        $params = $request->getParams();
        $station = $request->getStation();

        $mediaStorage = $station->getMediaStorageLocation();

        if ($mediaStorage->isStorageFull()) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, __('This station is out of available storage space.')));
        }

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        if (is_array($flowResponse)) {
            $currentDir = $request->getParam('currentDirectory', '');

            $destPath = $flowResponse['filename'];
            if (!empty($currentDir)) {
                $destPath = $currentDir . '/' . $destPath;
            }

            try {
                $stationMedia = $mediaRepo->getOrCreate($station, $destPath, $flowResponse['path']);
            } catch (CannotProcessMediaException $e) {
                $logger->error($e->getMessage(), [
                    'exception' => $e,
                ]);

                return $response->withJson(new Entity\Api\Status());
            }

            // If the user is looking at a playlist's contents, add uploaded media to that playlist.
            if (!empty($params['searchPhrase'])) {
                $search_phrase = $params['searchPhrase'];

                if (0 === strpos($search_phrase, 'playlist:')) {
                    $playlist_name = substr($search_phrase, 9);

                    $playlist = $em->getRepository(Entity\StationPlaylist::class)->findOneBy([
                        'station_id' => $station->getId(),
                        'name' => $playlist_name,
                    ]);

                    if ($playlist instanceof Entity\StationPlaylist) {
                        $spmRepo->addMediaToPlaylist($stationMedia, $playlist);
                        $em->flush();
                    }
                }
            }

            $mediaStorage->addStorageUsed($flowResponse['size']);
            $em->persist($mediaStorage);
            $em->flush();

            return $response->withJson(new Entity\Api\Status());
        }

        return $response->withJson(['success' => false]);
    }
}
