<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity\Repository\PodcastEpisodeRepository;
use App\Entity\Repository\PodcastRepository;
use App\Exception\PodcastNotFoundException;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;

class PodcastEpisodesController
{
    public function __construct(
        protected PodcastRepository $podcastRepository,
        protected PodcastEpisodeRepository $episodeRepository
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $podcast_id
    ): ResponseInterface {
        $router = $request->getRouter();
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcast_id);

        if ($podcast === null) {
            throw new PodcastNotFoundException();
        }

        $publishedEpisodes = $this->episodeRepository->fetchPublishedEpisodesForPodcast($podcast);

        // Reverse sort order according to the calculated publishing timestamp
        usort(
            $publishedEpisodes,
            static function ($prevEpisode, $nextEpisode) {
                $prevPublishedAt = $prevEpisode->getPublishedAt ?? $prevEpisode->getCreatedAt();
                $nextPublishedAt = $nextEpisode->getPublishedAt ?? $nextEpisode->getCreatedAt();

                return ($nextPublishedAt <=> $prevPublishedAt);
            }
        );

        $podcastsLink = (string)$router->fromHere(
            'public:podcasts',
            [
                'station_id' => $station->getId(),
            ]
        );

        if (count($publishedEpisodes) === 0) {
            $request->getFlash()->addMessage(__('No episodes found.'), Flash::ERROR);
            return $response->withRedirect($podcastsLink);
        }

        $feedLink = (string)$router->named(
            'public:podcast:feed',
            [
                'station_id' => $station->getId(),
                'podcast_id' => $podcast->getId(),
            ]
        );

        return $request->getView()->renderToResponse(
            $response,
            'frontend/public/podcast-episodes',
            [
                'episodes' => $publishedEpisodes,
                'feedLink' => $feedLink,
                'podcast' => $podcast,
                'podcastsLink' => $podcastsLink,
                'station' => $station,
            ]
        );
    }
}
