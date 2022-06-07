<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity;

/**
 * @extends AbstractAdminApiCrudController<Entity\ApiKey>
 */
final class ApiKeysController extends AbstractAdminApiCrudController
{
    protected string $entityClass = Entity\ApiKey::class;
    protected string $resourceRouteName = 'api:admin:api-key';
}
