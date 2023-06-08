<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\StationWebhook;

/**
 * @extends AbstractStationBasedRepository<StationWebhook>
 */
final class StationWebhookRepository extends AbstractStationBasedRepository
{
    protected string $entityClass = StationWebhook::class;
}
