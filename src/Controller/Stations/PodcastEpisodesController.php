<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\Repository\StationPodcastRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Slim\Routing\RouteContext;

class PodcastEpisodesController
{
    protected StationPodcastRepository $podcastRepository;

    public function __construct(StationPodcastRepository $podcastRepository) {
        $this->podcastRepository = $podcastRepository;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();
        $podcastId = (int) $routeArgs['podcast_id'];

        $station = $request->getStation();

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcastId);

        return $request->getView()->renderToResponse($response, 'stations/podcasts/episodes/index', [
            'stationId' => $station->getId(),
            'stationTz' => $station->getTimezone(),
            'podcast' => $podcast,
        ]);
    }
}
