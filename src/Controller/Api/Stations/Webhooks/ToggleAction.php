<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationWebhookRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ToggleAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationWebhookRepository $webhookRepo
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

        $newValue = !$record->getIsEnabled();
        $record->setIsEnabled($newValue);

        $em = $this->webhookRepo->getEntityManager();
        $em->persist($record);
        $em->flush();

        $flashMessage = ($newValue)
            ? __('Web hook enabled.')
            : __('Web hook disabled.');

        return $response->withJson(new Status(true, $flashMessage));
    }
}
