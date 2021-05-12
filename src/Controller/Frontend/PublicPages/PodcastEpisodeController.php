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
use Psr\Http\Message\ResponseInterface;
use Slim\Routing\RouteContext;

class PodcastEpisodeController
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

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $router = $request->getRouter();
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();
        $podcastId = (int) $routeArgs['podcast_id'];
        $episodeId = (int) $routeArgs['episode_id'];

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcastId);

        if ($podcast === null) {
            throw new PodcastNotFoundException();
        }

        $episode = $this->episodeRepository->fetchEpisodeForStation($station, $episodeId);

        $podcastEpisodesLink = (string) $router->named('public:podcast:episodes', [
            'station_id' => $station->getId(),
            'podcast_id' => $podcastId,
        ]);

        if (!$episode->isPublished()) {
            $request->getFlash()->addMessage(__('Episode not found.'), Flash::ERROR);

            $response->withRedirect($podcastEpisodesLink);
        }

        $feedLink = $router->named('public:podcast:feed', [
            'station_id' => $station->getId(),
            'podcast_id' => $podcast->getId(),
        ]);

        return $request->getView()->renderToResponse($response, 'frontend/public/podcast-episode', [
            'episode' => $episode,
            'feedLink' => $feedLink,
            'podcast' => $podcast,
            'podcastEpisodesLink' => $podcastEpisodesLink,
            'station' => $station,
        ]);
    }
}
