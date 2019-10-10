<?php
namespace App\Controller\Api\Station\Files;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

class UploadAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManager $em,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Entity\Repository\StationPlaylistMediaRepository $spmRepo
    ): ResponseInterface {
        $params = $request->getParams();
        $station = $request->getStation();

        if ($station->isStorageFull()) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, __('This station is out of available storage space.')));
        }

        try {
            $flow_response = Flow::process($request, $response, $station->getRadioTempDir());
            if ($flow_response instanceof ResponseInterface) {
                return $flow_response;
            }

            if (is_array($flow_response)) {
                $file = $request->getAttribute('file');
                $file_path = $request->getAttribute('file_path');

                $sanitized_name = $flow_response['filename'];

                $final_path = empty($file)
                    ? $file_path . $sanitized_name
                    : $file_path . '/' . $sanitized_name;

                $station_media = $mediaRepo->uploadFile($station, $flow_response['path'], $final_path);

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

                $station->addStorageUsed($flow_response['size']);
                $em->flush();

                return $response->withJson(new Entity\Api\Status);
            }
        } catch (\Exception | \Error $e) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, $e->getMessage()));
        }

        return $response->withJson(['success' => false]);
    }
}