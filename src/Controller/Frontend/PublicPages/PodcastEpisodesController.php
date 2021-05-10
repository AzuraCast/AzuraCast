<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity\Repository\StationPodcastEpisodeRepository;
use App\Entity\Repository\StationPodcastRepository;
use App\Exception\PodcastNotFoundException;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Slim\Routing\RouteContext;

class PodcastEpisodesController
{
    protected StationPodcastRepository $podcastRepository;
    protected StationPodcastEpisodeRepository $episodeRepository;

    public function __construct(
        StationPodcastRepository $podcastRepository,
        StationPodcastEpisodeRepository $episodeRepository
    ) {
        $this->podcastRepository = $podcastRepository;
        $this->episodeRepository = $episodeRepository;
    }

    public function __invoke(ServerRequest $request, Response $response): Response
    {
        $router = $request->getRouter();
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();
        $podcastId = (int) $routeArgs['podcast_id'];

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcastId);

        if ($podcast === null) {
            throw new PodcastNotFoundException();
        }

        $publishedEpisodes = $this->episodeRepository->fetchPublishedEpisodesForPodcast($podcast);

        // Reverse sort order according to the calculated publishing timestamp
        usort($publishedEpisodes, function($prevEpisode, $nextEpisode) {
            $prevPublishedAt = $prevEpisode->getPublishedAt ?? $prevEpisode->getCreatedAt();
            $nextPublishedAt = $nextEpisode->getPublishedAt ?? $nextEpisode->getCreatedAt();

            return ($nextPublishedAt <=> $prevPublishedAt);
        });

        $podcastsLink = (string) $router->fromHere('public:podcasts', [
            'station_id' => $station->getId(),
        ]);

        if (count($publishedEpisodes) === 0) {
            $request->getFlash()->addMessage(__('No episodes found.'), Flash::ERROR);

            $response->withRedirect($podcastsLink);
        }

        $feedLink = $router->named('public:podcast:feed', [
            'station_id' => $station->getId(),
            'podcast_id' => $podcast->getId()
        ]);

        return $request->getView()->renderToResponse($response, 'frontend/public/podcast-episodes', [
            'episodes' => $publishedEpisodes,
            'feedLink' => $feedLink,
            'podcast' => $podcast,
            'podcastsLink' => $podcastsLink,
            'station' => $station,
        ]);
    }
}
