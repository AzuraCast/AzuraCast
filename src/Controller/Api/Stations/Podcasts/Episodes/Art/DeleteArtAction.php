<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Art;

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
        Entity\Repository\PodcastEpisodeRepository $episodeRepo,
        EntityManagerInterface $em,
        string $episode_id
    ): ResponseInterface {
        $station = $request->getStation();

        $episode = $episodeRepo->fetchEpisodeForStation($station, $episode_id);
        if ($episode === null) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $episodeRepo->removeEpisodeArt($episode);
        $em->persist($episode);
        $em->flush();

        return $response->withJson(new Entity\Api\Status(true, __('Episode artwork successfully cleared.')));
    }
}
