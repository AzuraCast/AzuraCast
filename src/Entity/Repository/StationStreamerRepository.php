<?php
namespace App\Entity\Repository;

use App\Entity;

class StationStreamerRepository extends BaseRepository
{
    /**
     * Attempt to authenticate a streamer.
     *
     * @param Entity\Station $station
     * @param $username
     * @param $password
     * @return Entity\StationStreamer|bool
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

        return (strcmp($streamer->getStreamerPassword(), $password) === 0)
            ? $streamer
            : false;
    }
}
