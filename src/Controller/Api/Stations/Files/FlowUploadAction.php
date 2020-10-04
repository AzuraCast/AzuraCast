<?php
namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class FlowUploadAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Entity\Repository\StationPlaylistMediaRepository $spmRepo
    ): ResponseInterface {
        $params = $request->getParams();
        $station = $request->getStation();

        if ($station->isStorageFull()) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, __('This station is out of available storage space.')));
        }

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        if (is_array($flowResponse)) {
            $file = $request->getAttribute('file');
            $filePath = $request->getAttribute('file_path');

            $sanitizedName = $flowResponse['filename'];

            $finalPath = empty($file)
                ? $filePath . $sanitizedName
                : $filePath . '/' . $sanitizedName;

            $station_media = $mediaRepo->getOrCreate($station, $finalPath, $flowResponse['path']);

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
                        $spmRepo->addMediaToPlaylist($station_media, $playlist);
                        $em->flush();
                    }
                }
            }

            $station->addStorageUsed($flowResponse['size']);
            $em->flush();

            return $response->withJson(new Entity\Api\Status);
        }

        return $response->withJson(['success' => false]);
    }
}