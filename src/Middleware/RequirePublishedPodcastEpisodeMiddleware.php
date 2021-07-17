<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Acl;
use App\Entity\PodcastEpisode;
use App\Entity\Repository\PodcastRepository;
use App\Entity\Station;
use App\Entity\User;
use App\Exception\PodcastNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

/**
 * Require that the podcast has a published episode for public access
 */
class RequirePublishedPodcastEpisodeMiddleware
{
    public function __construct(
        protected PodcastRepository $podcastRepository
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->getLoggedInUser($request);
        $station = $request->getStation();

        if ($user !== null) {
            $acl = $request->getAcl();

            if ($this->canUserManageStationPodcasts($user, $station, $acl)) {
                return $handler->handle($request);
            }
        }

        $podcastId = $this->getPodcastIdFromRequest($request);

        if ($podcastId === null || !$this->checkPodcastHasPublishedEpisodes($station, $podcastId)) {
            throw new PodcastNotFoundException();
        }

        $response = $handler->handle($request);

        if ($response instanceof Response) {
            $response = $response->withNoCache();
        }

        return $response;
    }

    protected function getLoggedInUser(ServerRequest $request): ?User
    {
        try {
            return $request->getUser();
        } catch (Exception) {
            return null;
        }
    }

    protected function canUserManageStationPodcasts(User $user, Station $station, Acl $acl): bool
    {
        return $acl->userAllowed($user, Acl::STATION_PODCASTS, $station->getId());
    }

    protected function getPodcastIdFromRequest(ServerRequest $request): ?string
    {
        $routeArgs = RouteContext::fromRequest($request)->getRoute()?->getArguments();

        $podcastId = $routeArgs['id'] ?? null;

        if ($podcastId === null) {
            $podcastId = $routeArgs['podcast_id'] ?? null;
        }

        return $podcastId;
    }

    protected function checkPodcastHasPublishedEpisodes(Station $station, string $podcastId): bool
    {
        $podcastId = explode('|', $podcastId, 2)[0];

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcastId);

        if ($podcast === null) {
            return false;
        }

        /** @var PodcastEpisode $episode */
        foreach ($podcast->getEpisodes() as $episode) {
            if ($episode->isPublished()) {
                return true;
            }
        }

        return false;
    }
}
