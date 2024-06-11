<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts;

use App\Controller\SingleActionInterface;
use App\Entity\ApiGenerator\PodcastApiGenerator;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetPodcastAction implements SingleActionInterface
{
    public function __construct(
        private readonly PodcastApiGenerator $podcastApiGen
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $podcast = $request->getPodcast();

        return $response->withJson(
            $this->podcastApiGen->__invoke($podcast, $request)
        );
    }
}
