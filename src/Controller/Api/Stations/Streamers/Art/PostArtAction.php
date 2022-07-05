<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Streamers\Art;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;

final class PostArtAction
{
    public function __construct(
        private readonly Entity\Repository\StationStreamerRepository $streamerRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        ?string $id = null
    ): ResponseInterface {
        $station = $request->getStation();

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        if (null !== $id) {
            $streamer = $this->streamerRepo->requireForStation($id, $station);

            $this->streamerRepo->writeArtwork(
                $streamer,
                $flowResponse->readAndDeleteUploadedFile()
            );

            $this->streamerRepo->getEntityManager()
                ->flush();

            return $response->withJson(Entity\Api\Status::updated());
        }

        return $response->withJson($flowResponse);
    }
}
