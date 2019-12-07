<?php
namespace App\Radio\Remote;

use App\Entity;

class AdapterProxy
{
    /** @var AbstractRemote */
    protected AbstractRemote $adapter;

    /** @var Entity\StationRemote */
    protected Entity\StationRemote $remote;

    public function __construct(AbstractRemote $adapter, Entity\StationRemote $remote)
    {
        $this->adapter = $adapter;
        $this->remote = $remote;
    }

    /**
     * @return AbstractRemote
     */
    public function getAdapter(): AbstractRemote
    {
        return $this->adapter;
    }

    /**
     * @return Entity\StationRemote
     */
    public function getRemote(): Entity\StationRemote
    {
        return $this->remote;
    }
}
