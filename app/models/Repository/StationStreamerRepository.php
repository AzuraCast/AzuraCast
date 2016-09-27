<?php
namespace Repository;

use App\Doctrine\Repository;
use Entity\StationStreamer as Record;

class StationStreamerRepository extends Repository
{
    /**
     * Attempt to authenticate a streamer.
     *
     * @param \Entity\Station $station
     * @param $username
     * @param $password
     * @return bool
     */
    public function authenticate(\Entity\Station $station, $username, $password)
    {
        // Extra safety check for the station's streamer status.
        if (!$station->enable_streamers)
            return false;

        $streamer = $this->findOneBy([
            'station_id' => $station->id,
            'streamer_username' => $username,
            'is_active' => 1
        ]);

        if (!($streamer instanceof Record))
            return false;

        return (strcmp($streamer->streamer_password, $password) === 0);
    }
}