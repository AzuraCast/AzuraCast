<?php

namespace App\Event;

use App\Acl;
use App\Entity\Station;
use App\Entity\User;
use App\Http\RouterInterface;
use App\Radio\Backend\AbstractBackend;
use App\Radio\Frontend\AbstractFrontend;

class BuildStationMenu extends AbstractBuildMenu
{
    protected Station $station;

    protected AbstractBackend $backend;

    protected AbstractFrontend $frontend;

    public function __construct(
        Acl $acl,
        User $user,
        RouterInterface $router,
        Station $station,
        AbstractBackend $backend,
        AbstractFrontend $frontend
    ) {
        parent::__construct($acl, $user, $router);

        $this->station = $station;
        $this->backend = $backend;
        $this->frontend = $frontend;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getStationBackend(): AbstractBackend
    {
        return $this->backend;
    }

    public function getStationFrontend(): AbstractFrontend
    {
        return $this->frontend;
    }

    public function checkPermission(string $permission_name): bool
    {
        return $this->acl->userAllowed($this->user, $permission_name, $this->station->getId());
    }
}
