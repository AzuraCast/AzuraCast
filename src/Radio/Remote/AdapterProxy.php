<?php
namespace App\Radio\Remote;

use App\Entity;

class AdapterProxy
{
    /** @var RemoteAbstract */
    protected $adapter;

    /** @var Entity\StationRemote */
    protected $remote;

    public function __construct(RemoteAbstract $adapter, Entity\StationRemote $remote)
    {
        $this->adapter = $adapter;
        $this->remote = $remote;
    }

    /**
     * @return RemoteAbstract
     */
    public function getAdapter(): RemoteAbstract
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
