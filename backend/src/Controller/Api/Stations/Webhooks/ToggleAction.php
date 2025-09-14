<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationWebhookRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Put(
    path: '/station/{station_id}/webhook/{id}/toggle',
    operationId: 'putStationWebhookToggle',
    summary: 'Toggle the enabled/disabled status of a webhook.',
    tags: [OpenApi::TAG_STATIONS_WEBHOOKS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'id',
            description: 'Web Hook ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', format: 'int64')
        ),
    ],
    responses: [
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class ToggleAction implements SingleActionInterface
{
    public function __construct(
        private StationWebhookRepository $webhookRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        $record = $this->webhookRepo->requireForStation($id, $request->getStation());

        $newValue = !$record->is_enabled;
        $record->is_enabled = $newValue;

        $em = $this->webhookRepo->getEntityManager();
        $em->persist($record);
        $em->flush();

        $flashMessage = ($newValue)
            ? __('Web hook enabled.')
            : __('Web hook disabled.');

        return $response->withJson(new Status(true, $flashMessage));
    }
}
