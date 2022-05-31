<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Entity;
use App\Entity\Repository\StationWebhookRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ToggleAction
{
    public function __construct(
        private readonly StationWebhookRepository $webhookRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id
    ): ResponseInterface {
        $record = $this->webhookRepo->requireForStation($id, $request->getStation());

        $newValue = !$record->getIsEnabled();
        $record->setIsEnabled($newValue);

        $em = $this->webhookRepo->getEntityManager();
        $em->persist($record);
        $em->flush();

        $flash_message = ($newValue)
            ? __('Web hook enabled.')
            : __('Web hook disabled.');

        return $response->withJson(new Entity\Api\Status(true, $flash_message));
    }
}
