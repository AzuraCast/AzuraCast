<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity\Repository\PodcastRepository;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PodcastsController
{
    public function __construct(
        private readonly PodcastRepository $podcastRepository
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $publishedPodcasts = $this->podcastRepository->fetchPublishedPodcastsForStation($station);

        return $request->getView()->renderToResponse(
            $response
                ->withHeader('X-Frame-Options', '*')
                ->withHeader('X-Robots-Tag', 'index, nofollow'),
            'frontend/public/podcasts',
            [
                'podcasts' => $publishedPodcasts,
                'station' => $station,
            ]
        );
    }
}
