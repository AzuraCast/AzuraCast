<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Settings;
use App\Entity\Station;
use App\Enums\PermissionInterface;
use App\Http\ServerRequest;

final class BuildStationMenu extends AbstractBuildMenu
{
    public function __construct(
        private readonly Station $station,
        ServerRequest $request,
        Settings $settings
    ) {
        parent::__construct($request, $settings);
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function checkPermission(string|PermissionInterface $permissionName): bool
    {
        return $this->request->getAcl()->isAllowed($permissionName, $this->station->getId());
    }
}
