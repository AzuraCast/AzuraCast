<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity\Repository\PodcastRepository;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class PodcastsController
{
    public function __construct(
        protected PodcastRepository $podcastRepository
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
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
