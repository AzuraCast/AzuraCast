<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Acl;
use App\Entity\Repository\StationPodcastRepository;
use App\Entity\Station;
use App\Entity\StationPodcastEpisode;
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
    protected StationPodcastRepository $podcastRepository;

    public function __construct(StationPodcastRepository $podcastRepository)
    {
        $this->podcastRepository = $podcastRepository;
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
        } catch (Exception $e) {
            return null;
        }
    }

    protected function canUserManageStationPodcasts(User $user, Station $station, Acl $acl): bool
    {
        return $acl->userAllowed($user, Acl::STATION_PODCASTS, $station->getId());
    }

    protected function getPodcastIdFromRequest(ServerRequest $request): ?int
    {
        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();

        $podcastId = $routeArgs['id'] ?? null;

        if ($podcastId === null) {
            $podcastId = $routeArgs['podcast_id'];
        }

        return $podcastId === null ? null : (int) $podcastId;
    }

    protected function checkPodcastHasPublishedEpisodes(Station $station, int $podcastId): bool
    {
        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcastId);

        if ($podcast === null) {
            return false;
        }

        /** @var StationPodcastEpisode $episode */
        foreach ($podcast->getEpisodes() as $episode) {
            if ($episode->isPublished()) {
                return true;
            }
        }

        return false;
    }
}
