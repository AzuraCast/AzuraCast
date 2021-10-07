<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Entity;
use App\Exception\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractWebhooksAction
{
    public function __construct(
        protected EntityManagerInterface $em
    ) {
    }

    protected function requireRecord(Entity\Station $station, int $id): Entity\StationWebhook
    {
        $record = $this->em->getRepository(Entity\StationWebhook::class)->findOneBy(
            [
                'station' => $station,
                'id' => $id,
            ]
        );

        if (!$record instanceof Entity\StationWebhook) {
            throw new NotFoundException(__('Web hook not found.'));
        }

        return $record;
    }
}
