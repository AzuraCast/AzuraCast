<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Entity\Podcast;
use App\Entity\Repository\PodcastRepository;
use App\Exception\NotFoundException;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

/**
 * Retrieve the podcast specified in the request parameters.
 */
final class GetAndRequirePodcast extends AbstractMiddleware
{
    public function __construct(
        private readonly PodcastRepository $podcastRepo
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeArgs = RouteContext::fromRequest($request)->getRoute()?->getArguments();

        $id = $routeArgs['podcast_id'] ?? $routeArgs['id'] ?? null;

        if (empty($id)) {
            throw NotFoundException::podcast();
        }

        $record = $this->podcastRepo->fetchPodcastForStation(
            $request->getStation(),
            $id
        );

        if (!($record instanceof Podcast)) {
            throw NotFoundException::podcast();
        }

        $request = $request->withAttribute(ServerRequest::ATTR_PODCAST, $record);

        return $handler->handle($request);
    }
}
