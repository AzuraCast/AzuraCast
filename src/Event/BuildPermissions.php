<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class BuildPermissions extends Event
{
    public function __construct(
        private array $permissions
    ) {
    }

    /**
     * @return mixed[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }
}
