<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Media;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;

class PostMediaAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\PodcastEpisodeRepository $episodeRepo,
        Entity\Repository\PodcastMediaRepository $mediaRepo,
        string $podcast_id,
        ?string $episode_id = null
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

            $fsStation = new StationFilesystems($station);
            $mediaRepo->upload(
                $episode,
                $flowResponse->getOriginalFilename(),
                $flowResponse->getUploadedPath(),
                $fsStation->getPodcastsFilesystem()
            );

            return $response->withJson(new Entity\Api\Status());
        }

        return $response->withJson($flowResponse);
    }
}
