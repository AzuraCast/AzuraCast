<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity\Repository\StationPodcastRepository;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;

class PodcastsController
{
    protected StationPodcastRepository $podcastRepository;

    public function __construct(StationPodcastRepository $podcastRepository)
    {
        $this->podcastRepository = $podcastRepository;
    }

    public function __invoke(ServerRequest $request, Response $response): Response
    {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $publishedPodcasts = $this->podcastRepository->fetchPublishedPodcastsForStation($station);

        return $request->getView()->renderToResponse($response, 'frontend/public/podcasts', [
            'podcasts' => $publishedPodcasts,
            'station' => $station,
        ]);
    }
}
