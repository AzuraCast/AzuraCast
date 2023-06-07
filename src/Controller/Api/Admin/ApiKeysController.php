<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\ApiKey;

/**
 * @extends AbstractAdminApiCrudController<ApiKey>
 */
final class ApiKeysController extends AbstractAdminApiCrudController
{
    protected string $entityClass = ApiKey::class;
    protected string $resourceRouteName = 'api:admin:api-key';
}
