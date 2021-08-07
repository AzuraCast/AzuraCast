<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Media;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class DeleteMediaAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\PodcastMediaRepository $mediaRepo,
        Entity\Repository\PodcastEpisodeRepository $episodeRepo,
        string $episode_id
    ): ResponseInterface {
        $station = $request->getStation();
        $episode = $episodeRepo->fetchEpisodeForStation($station, $episode_id);

        if (!($episode instanceof Entity\PodcastEpisode)) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $podcastMedia = $episode->getMedia();

        if ($podcastMedia instanceof Entity\PodcastMedia) {
            $mediaRepo->delete($podcastMedia);
        }

        return $response->withJson(new Entity\Api\Status());
    }
}
