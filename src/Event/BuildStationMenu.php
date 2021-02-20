<?php

namespace App\Event;

use App\Entity\Settings;
use App\Entity\Station;
use App\Http\ServerRequest;

class BuildStationMenu extends AbstractBuildMenu
{
    protected Station $station;

    public function __construct(ServerRequest $request, Settings $settings, Station $station)
    {
        parent::__construct($request, $settings);

        $this->station = $station;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function checkPermission(string $permission_name): bool
    {
        $acl = $this->request->getAcl();
        return $acl->isAllowed($permission_name, $this->station->getId());
    }
}
