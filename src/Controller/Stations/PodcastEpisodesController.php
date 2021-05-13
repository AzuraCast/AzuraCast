<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\Repository\PodcastRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class PodcastEpisodesController
{
    public function __construct(
        protected PodcastRepository $podcastRepository
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        int $podcast_id
    ): ResponseInterface {
        $station = $request->getStation();
        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcast_id);

        return $request->getView()->renderToResponse(
            $response,
            'stations/podcasts/episodes/index',
            [
                'stationId' => $station->getId(),
                'stationTz' => $station->getTimezone(),
                'podcast' => $podcast,
            ]
        );
    }
}
