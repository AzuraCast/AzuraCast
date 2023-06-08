<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\ApiKey;

/**
 * @extends AbstractSplitTokenRepository<ApiKey>
 */
final class ApiKeyRepository extends AbstractSplitTokenRepository
{
    protected string $entityClass = ApiKey::class;
}
