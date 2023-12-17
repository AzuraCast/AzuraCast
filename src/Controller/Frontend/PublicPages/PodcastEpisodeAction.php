<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\SingleActionInterface;
use App\Entity\PodcastEpisode;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Entity\Repository\PodcastRepository;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PodcastEpisodeAction implements SingleActionInterface
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

        /** @var string $episodeId */
        $episodeId = $params['episode_id'];

        $router = $request->getRouter();
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw NotFoundException::station();
        }

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcastId);

        if ($podcast === null) {
            throw NotFoundException::podcast();
        }

        $episode = $this->episodeRepository->fetchEpisodeForStation($station, $episodeId);

        $podcastEpisodesLink = $router->named(
            'public:podcast:episodes',
            [
                'station_id' => $station->getId(),
                'podcast_id' => $podcastId,
            ]
        );

        if (!($episode instanceof PodcastEpisode) || !$episode->isPublished()) {
            $request->getFlash()->error(__('Episode not found.'));
            return $response->withRedirect($podcastEpisodesLink);
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
            'frontend/public/podcast-episode',
            [
                'episode' => $episode,
                'feedLink' => $feedLink,
                'podcast' => $podcast,
                'podcastEpisodesLink' => $podcastEpisodesLink,
                'station' => $station,
            ]
        );
    }
}
