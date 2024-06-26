<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Waveform;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationMediaRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\Types;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

final class PostCacheWaveformAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationMediaRepository $mediaRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $mediaId */
        $mediaId = $params['media_id'];

        $station = $request->getStation();

        $media = $this->mediaRepo->requireByUniqueId($mediaId, $station);

        $waveformData = Types::arrayOrNull($request->getParsedBody());
        if (empty($waveformData) || empty($waveformData['data'])) {
            throw new InvalidArgumentException('No waveform data provided.');
        }

        $this->mediaRepo->saveWaveformData($media, $waveformData);

        return $response->withJson(Status::updated());
    }
}
