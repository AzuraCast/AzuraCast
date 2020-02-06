<?php
namespace App\Entity\Repository;

use App\Entity;
use App\Radio\Adapters;
use App\Doctrine\Repository;

class StationStreamerRepository extends Repository
{
    /**
     * Attempt to authenticate a streamer.
     *
     * @param Entity\Station $station
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function authenticate(
        Entity\Station $station,
        string $username = '',
        string $password = ''
    ): bool {
        // Extra safety check for the station's streamer status.
        if (!$station->getEnableStreamers()) {
            return false;
        }

        $streamer = $this->getStreamer($station, $username);
        if (!($streamer instanceof Entity\StationStreamer)) {
            return false;
        }

        return $streamer->authenticate($password);
    }

    /**
     * @param Entity\Station $station
     * @param string $username
     *
     * @return string|bool
     */
    public function onConnect(Entity\Station $station, string $username = '')
    {
        // End all current streamer sessions.
        $this->clearBroadcastsForStation($station);

        $streamer = $this->getStreamer($station, $username);
        if (!($streamer instanceof Entity\StationStreamer)) {
            return false;
        }

        $station->setIsStreamerLive(true);
        $station->setCurrentStreamer($streamer);
        $this->em->persist($station);

        $record = new Entity\StationStreamerBroadcast($streamer);
        $this->em->persist($record);

        if (Adapters::BACKEND_LIQUIDSOAP === $station->getBackendType()) {
            $backendConfig = (array)$station->getBackendConfig();
            $recordStreams = (bool)($backendConfig['record_streams'] ?? false);

            if ($recordStreams) {
                $format = $backendConfig['record_streams_format'] ?? Entity\StationMountInterface::FORMAT_MP3;
                $recordingPath = $record->generateRecordingPath($format);

                $this->em->persist($record);
                $this->em->flush();

                return $station->getRadioRecordingsDir() . '/' . $recordingPath;
            }
        }

        $this->em->flush();
        return true;
    }

    public function onDisconnect(Entity\Station $station): bool
    {
        $station->setIsStreamerLive(false);
        $station->setCurrentStreamer(null);
        $this->em->persist($station);
        $this->em->flush();

        $this->clearBroadcastsForStation($station);
        return true;
    }

    protected function clearBroadcastsForStation(Entity\Station $station): void
    {
        $this->em->createQuery(/** @lang DQL */ 'UPDATE App\Entity\StationStreamerBroadcast ssb
            SET ssb.timestampEnd = :time
            WHERE ssb.station = :station
            AND ssb.timestampEnd = 0')
            ->setParameter('time', time())
            ->setParameter('station', $station)
            ->execute();
    }

    protected function getStreamer(Entity\Station $station, string $username = ''): ?Entity\StationStreamer
    {
        /** @var Entity\StationStreamer|null $streamer */
        $streamer = $this->repository->findOneBy([
            'station' => $station,
            'streamer_username' => $username,
            'is_active' => 1,
        ]);

        return $streamer;
    }

    /**
     * Fetch all streamers who are deactivated and have a reactivate at timestamp set
     *
     * @param int $reactivate_at
     *
     * @return Entity\StationStreamer[]
     */
    public function getStreamersDueForReactivation(int $reactivate_at = null): array
    {
        $reactivate_at = $reactivate_at ?? time();

        return $this->repository->createQueryBuilder('s')
            ->where('s.is_active = 0')
            ->andWhere('s.reactivate_at <= :reactivate_at')
            ->setParameter('reactivate_at', $reactivate_at)
            ->getQuery()
            ->execute();
    }
}
