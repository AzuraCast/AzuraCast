<?php
namespace App\Entity\Repository;

use App\Entity;
use Azura\Doctrine\Repository;

class StationStreamerRepository extends Repository
{
    /**
     * Attempt to authenticate a streamer.
     *
     * @param Entity\Station $station
     * @param string $username
     * @param string $password
     *
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
            'is_active' => 1,
        ]);

        if (!($streamer instanceof Entity\StationStreamer)) {
            return false;
        }

        return (strcmp($streamer->getStreamerPassword(), $password) === 0)
            ? $streamer
            : false;
    }

    /**
     * Fetch all streamers who are deactivated and have a reactivate at timestamp set
     *
     * @param int $reactivate_at
     *
     * @return Entity\StationStreamer[]
     */
    public function getStreamersDueForReactivation(int $reactivate_at = null)
    {
        $reactivate_at = $reactivate_at ?? time();

        return $this->createQueryBuilder('s')
            ->where('s.is_active = 0')
            ->andWhere('s.reactivate_at <= :reactivate_at')
            ->setParameter('reactivate_at', $reactivate_at)
            ->getQuery()
            ->execute();
    }
}
