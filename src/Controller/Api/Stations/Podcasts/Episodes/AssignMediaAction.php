<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class AssignMediaAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\PodcastEpisodeRepository $episodeRepo,
        Entity\Repository\PodcastMediaRepository $mediaRepo,
        EntityManagerInterface $em,
        string $podcast_media_id,
        string $episode_id,
    ): ResponseInterface {
        $station = $request->getStation();

        $episode = $episodeRepo->fetchEpisodeForStation($station, $episode_id);
        $podcastMedia = $mediaRepo->fetchPodcastMediaForStation($station, $podcast_media_id);

        if ($episode === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Episode not found!')));
        }

        if ($podcastMedia === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Podcast media not found!')));
        }

        $episode->setMedia($podcastMedia);
        $em->persist($episode);

        $podcastMedia->setEpisode($episode);
        $em->persist($podcastMedia);
        $em->flush();

        return $response->withJson(new Entity\Api\Status(true, __('Podcast media successfully assigned to episode.')));
    }
}
