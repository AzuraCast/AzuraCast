<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Settings;
use App\Entity\Station;
use App\Http\ServerRequest;

class BuildStationMenu extends AbstractBuildMenu
{
    public function __construct(
        protected Station $station,
        ServerRequest $request,
        Settings $settings
    ) {
        parent::__construct($request, $settings);
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function checkPermission(string $permission_name): bool
    {
        return $this->request->getAcl()->isAllowed($permission_name, $this->station->getId());
    }
}
