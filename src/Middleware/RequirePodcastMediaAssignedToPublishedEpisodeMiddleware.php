<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Acl;
use App\Entity\Repository\PodcastMediaRepository;
use App\Entity\Station;
use App\Entity\User;
use App\Exception\PodcastMediaNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

/**
 * Require that the podcast media is assigned to a published episode for public access
 */
class RequirePodcastMediaAssignedToPublishedEpisodeMiddleware
{
    public function __construct(
        protected PodcastMediaRepository $podcastMediaRepository
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

        $podcastMediaId = $this->getPodcastMediaIdFromRequest($request);

        if (
            $podcastMediaId === null
            || !$this->checkPodcastMediaAssignedToPublishedEpisodes($station, $podcastMediaId)
        ) {
            throw new PodcastMediaNotFoundException();
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
        } catch (Exception $e) {
            return null;
        }
    }

    protected function canUserManageStationPodcasts(User $user, Station $station, Acl $acl): bool
    {
        return $acl->userAllowed($user, Acl::STATION_PODCASTS, $station->getId());
    }

    protected function getPodcastMediaIdFromRequest(ServerRequest $request): ?int
    {
        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();

        $podcastMediaId = $routeArgs['id'] ?? null;

        if ($podcastMediaId === null) {
            $podcastMediaId = $routeArgs['podcast_media_id'];
        }

        return $podcastMediaId === null ? null : (int) $podcastMediaId;
    }

    protected function checkPodcastMediaAssignedToPublishedEpisodes(Station $station, int $podcastMediaId): bool
    {
        $podcastMedia = $this->podcastMediaRepository->fetchPodcastMediaForStation($station, $podcastMediaId);

        if ($podcastMedia === null) {
            return false;
        }

        $episode = $podcastMedia->getEpisode();

        if ($episode === null) {
            return false;
        }

        return $episode->isPublished();
    }
}
