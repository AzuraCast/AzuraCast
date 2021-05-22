<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Art;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class DeleteArtAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\PodcastRepository $podcastRepo,
        EntityManagerInterface $em,
        string $podcast_id,
    ): ResponseInterface {
        $station = $request->getStation();

        $podcast = $podcastRepo->fetchPodcastForStation($station, $podcast_id);

        if ($podcast === null) {
            return $response->withStatus(404)
                ->withJson(
                    new Entity\Api\Error(
                        404,
                        __('Podcast not found!')
                    )
                );
        }

        $podcastRepo->removePodcastArt($podcast);
        $em->persist($podcast);
        $em->flush();

        return $response->withJson(
            new Entity\Api\Status(
                true,
                __('Podcast artwork successfully cleared.')
            )
        );
    }
}
