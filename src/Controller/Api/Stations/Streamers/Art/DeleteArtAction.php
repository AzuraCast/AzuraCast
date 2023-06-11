<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Streamers\Art;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationStreamerRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class DeleteArtAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationStreamerRepository $streamerRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        $station = $request->getStation();

        $streamer = $this->streamerRepo->requireForStation($id, $station);

        $this->streamerRepo->removeArtwork($streamer);
        $this->streamerRepo->getEntityManager()
            ->flush();

        return $response->withJson(Status::deleted());
    }
}
