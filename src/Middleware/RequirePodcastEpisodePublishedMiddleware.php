<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Acl;
use App\Entity\Repository\StationPodcastEpisodeRepository;
use App\Entity\Station;
use App\Entity\User;
use App\Exception\PodcastEpisodeNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

/**
 * Require that the episode is published for public access
 */
class RequirePodcastEpisodePublishedMiddleware
{
    protected StationPodcastEpisodeRepository $episodeRepository;

    public function __construct(StationPodcastEpisodeRepository $episodeRepository)
    {
        $this->episodeRepository = $episodeRepository;
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

        $episodeId = $this->getEpisodeIdFromRequest($request);

        if ($episodeId === null || !$this->checkIsEpisodePublished($station, $episodeId)) {
            throw new PodcastEpisodeNotFoundException();
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

    protected function getEpisodeIdFromRequest(ServerRequest $request): ?int
    {
        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();

        $episodeId = $routeArgs['id'] ?? null;

        if ($episodeId === null) {
            $episodeId = $routeArgs['episode_id'];
        }

        return $episodeId === null ? null : (int) $episodeId;
    }

    protected function checkIsEpisodePublished(Station $station, int $episodeId): bool
    {
        $episode = $this->episodeRepository->fetchEpisodeForStation($station, $episodeId);

        if ($episode === null) {
            return false;
        }

        return $episode->isPublished();
    }
}
