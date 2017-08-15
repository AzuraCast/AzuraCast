<?php
namespace Entity\Repository;

use Entity;

class StationStreamerRepository extends \App\Doctrine\Repository
{
    /**
     * Attempt to authenticate a streamer.
     *
     * @param Entity\Station $station
     * @param $username
     * @param $password
     * @return bool
     */
    public function authenticate(Entity\Station $station, $username, $password)
    {
        // Extra safety check for the station's streamer status.
        if (!$station->getEnableStreamers()) {
            return false;
        }

        $streamer = $this->findOneBy([
            'station_id' => $station->getId(),
            'streamer_username' => $username,
            'is_active' => 1
        ]);

        if (!($streamer instanceof Entity\StationStreamer)) {
            return false;
        }

        return (strcmp($streamer->getStreamerPassword(), $password) === 0);
    }
}