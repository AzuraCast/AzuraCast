<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes;

use App\Controller\SingleActionInterface;
use App\Entity\ApiGenerator\PodcastEpisodeApiGenerator;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\Types;
use Psr\Http\Message\ResponseInterface;

final class GetEpisodeAction implements SingleActionInterface
{
    public function __construct(
        private readonly PodcastEpisodeRepository $episodeRepo,
        private readonly PodcastEpisodeApiGenerator $episodeApiGen
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $episodeId = Types::string($params['episode_id'] ?? null);

        $episode = $this->episodeRepo->fetchEpisodeForPodcast(
            $request->getPodcast(),
            $episodeId
        );

        if (null === $episode) {
            throw NotFoundException::podcast();
        }

        return $response->withJson(
            $this->episodeApiGen->__invoke($episode, $request)
        );
    }
}
