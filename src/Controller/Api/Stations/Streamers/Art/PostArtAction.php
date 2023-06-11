<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Streamers\Art;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationStreamerRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;

final class PostArtAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationStreamerRepository $streamerRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string|null $id */
        $id = $params['id'] ?? null;

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

            return $response->withJson(Status::updated());
        }

        return $response->withJson($flowResponse);
    }
}
