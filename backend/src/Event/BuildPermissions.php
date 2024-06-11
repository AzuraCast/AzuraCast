<?php

declare(strict_types=1);

namespace App\Event;

use App\Acl;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @phpstan-import-type PermissionsArray from Acl
 */
final class BuildPermissions extends Event
{
    /**
     * @param PermissionsArray $permissions
     */
    public function __construct(
        private array $permissions
    ) {
    }

    /**
     * @return PermissionsArray
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @param PermissionsArray $permissions
     */
    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }
}
