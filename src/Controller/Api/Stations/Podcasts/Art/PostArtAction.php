<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Art;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;

class PostArtAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\PodcastRepository $podcastRepo,
        ?string $podcast_id
    ): ResponseInterface {
        $station = $request->getStation();

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        if (null !== $podcast_id) {
            $podcast = $podcastRepo->fetchPodcastForStation($station, $podcast_id);

            if (null === $podcast) {
                return $response->withStatus(404)
                    ->withJson(Entity\Api\Error::notFound());
            }

            $podcastRepo->writePodcastArt(
                $podcast,
                $flowResponse->readAndDeleteUploadedFile()
            );

            return $response->withJson(new Entity\Api\Status());
        }

        return $response->withJson($flowResponse);
    }
}
