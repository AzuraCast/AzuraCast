<?php
namespace App\RadioBackend;

use Entity\Station;

class AdapterAbstract
{
    protected $station;

    /**
     * @param Station $station
     */
    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    /**
     * Read configuration from external service to Station object.
     * @return bool
     */
    public function read()
    {
        return false;
    }

    /**
     * Write configuration from Station object to the external service.
     * @return bool
     */
    public function write()
    {
        return false;
    }

    /**
     * Restart the executable service.
     * @return mixed
     */
    public function restart()
    {
        return null;
    }
}