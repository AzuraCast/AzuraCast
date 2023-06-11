<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\SingleActionInterface;
use App\Entity\PodcastEpisode;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Entity\Repository\PodcastRepository;
use App\Exception\PodcastNotFoundException;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PodcastEpisodesAction implements SingleActionInterface
{
    public function __construct(
        private readonly PodcastRepository $podcastRepository,
        private readonly PodcastEpisodeRepository $episodeRepository
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $podcastId */
        $podcastId = $params['podcast_id'];

        $router = $request->getRouter();
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcastId);

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
            $request->getFlash()->error(__('No episodes found.'));
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
