<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Files;

use App\Entity;
use App\Entity\Repository\StationPodcastMediaRepository;
use App\Entity\StationPodcastMedia;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Routing\RouteContext;

class DeleteAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $entityManager,
        StationPodcastMediaRepository $podcastMediaRepository
    ): ResponseInterface {
        $station = $request->getStation();

        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();
        $podcastMediaId = (int) $routeArgs['podcast_media_id'];

        $stationFilesystems = new StationFilesystems($station);
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        /** @var StationPodcastMedia $podcastMedia */
        $podcastMedia = $podcastMediaRepository->getRepository()->findOneBy([
            'station' => $station,
            'id' => $podcastMediaId,
        ]);

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
