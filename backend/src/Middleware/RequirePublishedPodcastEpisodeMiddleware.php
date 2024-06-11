<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Entity\Repository\PodcastRepository;
use App\Exception\NotFoundException;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Require that the podcast has a published episode for public access
 */
final class RequirePublishedPodcastEpisodeMiddleware extends AbstractMiddleware
{
    public function __construct(
        private readonly PodcastRepository $podcastRepository
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $station = $request->getStation();

        $publishedPodcastIds = $this->podcastRepository->getPodcastIdsWithPublishedEpisodes($station);

        $podcast = $request->getPodcast();

        if (!$podcast->isEnabled() || !in_array($podcast->getIdRequired(), $publishedPodcastIds, true)) {
            throw NotFoundException::podcast();
        }

        return $handler->handle($request);
    }
}
