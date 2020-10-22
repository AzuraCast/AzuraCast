<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BuildPermissions extends Event
{
    protected array $permissions;

    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;
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
