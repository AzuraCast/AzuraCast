<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ToggleAction extends AbstractWebhooksAction
{
    public function __invoke(ServerRequest $request, Response $response, int $id): ResponseInterface
    {
        $record = $this->requireRecord($request->getStation(), $id);

        $newValue = !$record->getIsEnabled();
        $record->setIsEnabled($newValue);

        $this->em->persist($record);
        $this->em->flush();

        $flash_message = ($newValue)
            ? __('Web hook enabled.')
            : __('Web hook disabled.');

        return $response->withJson(new Entity\Api\Status(true, $flash_message));
    }
}
