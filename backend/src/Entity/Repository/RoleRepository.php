<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\Role;

/**
 * @extends Repository<Role>
 */
final class RoleRepository extends Repository
{
    protected string $entityClass = Role::class;
}
