<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Art;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class PostArtAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\PodcastEpisodeRepository $episodeRepo,
        EntityManagerInterface $em,
        ?string $episode_id
    ): ResponseInterface {
        $station = $request->getStation();

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        if (null !== $episode_id) {
            $episode = $episodeRepo->fetchEpisodeForStation($station, $episode_id);

            if (null === $episode) {
                return $response->withStatus(404)
                    ->withJson(Entity\Api\Error::notFound());
            }

            $episodeRepo->writeEpisodeArt(
                $episode,
                $flowResponse->readAndDeleteUploadedFile()
            );

            $em->flush();

            return $response->withJson(Entity\Api\Status::updated());
        }

        return $response->withJson($flowResponse);
    }
}
