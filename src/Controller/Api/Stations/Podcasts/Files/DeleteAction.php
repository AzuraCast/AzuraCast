<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Files;

use App\Entity;
use App\Entity\PodcastMedia;
use App\Entity\Repository\PodcastMediaRepository;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class DeleteAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $entityManager,
        PodcastMediaRepository $podcastMediaRepository,
        int $podcast_media_id
    ): ResponseInterface {
        $station = $request->getStation();

        $stationFilesystems = new StationFilesystems($station);
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        /** @var PodcastMedia $podcastMedia */
        $podcastMedia = $podcastMediaRepository->getRepository()->findOneBy(
            [
                'station' => $station,
                'id' => $podcast_media_id,
            ]
        );

        if (null === $podcastMedia) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $podcastMediaRepository->removePodcastArtwork($podcastMedia);

        $podcastMediaFilePath = $podcastMedia->getPath();

        $entityManager->remove($podcastMedia);
        $entityManager->flush();

        $fileMeta = $podcastsFilesystem->getMetadata($podcastMediaFilePath);

        $station->getPodcastsStorageLocation()->removeStorageUsed($fileMeta['size']);

        $podcastsFilesystem->delete($podcastMediaFilePath);

        $entityManager->persist($station);
        $entityManager->flush();

        return $response->withJson(new Entity\Api\Status(true, __('Record deleted successfully.')));
    }
}
