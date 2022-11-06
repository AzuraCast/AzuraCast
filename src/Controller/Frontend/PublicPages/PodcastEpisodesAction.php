<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity\PodcastEpisode;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Entity\Repository\PodcastRepository;
use App\Exception\PodcastNotFoundException;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;

final class PodcastEpisodesAction
{
    public function __construct(
        private readonly PodcastRepository $podcastRepository,
        private readonly PodcastEpisodeRepository $episodeRepository
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
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
                /** @var PodcastEpisode $prevEpisode */
                /** @var PodcastEpisode $nextEpisode */

                $prevPublishedAt = $prevEpisode->getPublishAt() ?? $prevEpisode->getCreatedAt();
                $nextPublishedAt = $nextEpisode->getPublishAt() ?? $nextEpisode->getCreatedAt();

                return ($nextPublishedAt <=> $prevPublishedAt);
            }
        );

        $podcastsLink = $router->fromHere(
            'public:podcasts',
            [
                'station_id' => $station->getId(),
            ]
        );

        if (count($publishedEpisodes) === 0) {
            $request->getFlash()->addMessage(__('No episodes found.'), Flash::ERROR);
            return $response->withRedirect($podcastsLink);
        }

        $feedLink = $router->named(
            'public:podcast:feed',
            [
                'station_id' => $station->getId(),
                'podcast_id' => $podcast->getId(),
            ]
        );

        return $request->getView()->renderToResponse(
            $response
                ->withHeader('X-Frame-Options', '*')
                ->withHeader('X-Robots-Tag', 'index, nofollow'),
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
