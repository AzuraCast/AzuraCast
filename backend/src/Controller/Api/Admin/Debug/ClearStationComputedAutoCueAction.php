<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationMediaRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Put(
        path: '/admin/debug/station/{station_id}/clear-computed-autocue',
        operationId: 'adminDebugClearStationComputedAutoCue',
        summary: 'Clear the AutoCue values Liquidsoap computed for the media of this station.',
        tags: [OpenApi::TAG_ADMIN_DEBUG],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
]
final readonly class ClearStationComputedAutoCueAction implements SingleActionInterface
{
    public function __construct(
        private StationMediaRepository $mediaRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $clearedCount = $this->mediaRepo->clearComputedAutoCue(
            $request->getStation()->media_storage_location
        );

        return $response->withJson(
            new Status(true, sprintf(__('Cleared computed AutoCue values for %d media.'), $clearedCount))
        );
    }
}
