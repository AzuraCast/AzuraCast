<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Media\AlbumArt;
use App\Radio\AutoDJ\Scheduler;

/**
 * @extends AbstractStationBasedRepository<Entity\StationStreamer>
 */
final class StationStreamerRepository extends AbstractStationBasedRepository
{
    public function __construct(
        ReloadableEntityManagerInterface $em,
        private readonly Scheduler $scheduler,
        private readonly StationStreamerBroadcastRepository $broadcastRepo
    ) {
        parent::__construct($em);
    }

    /**
     * Attempt to authenticate a streamer.
     *
     * @param Entity\Station $station
     * @param string $username
     * @param string $password
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

        return $streamer->authenticate($password) && $this->scheduler->canStreamerStreamNow($streamer);
    }

    /**
     * @param Entity\Station $station
     * @param string $username
     *
     */
    public function onConnect(Entity\Station $station, string $username = ''): string|bool
    {
        // End all current streamer sessions.
        $this->broadcastRepo->endAllActiveBroadcasts($station);

        $streamer = $this->getStreamer($station, $username);
        if (!($streamer instanceof Entity\StationStreamer)) {
            return false;
        }

        $station->setIsStreamerLive(true);
        $station->setCurrentStreamer($streamer);
        $this->em->persist($station);

        $record = new Entity\StationStreamerBroadcast($streamer);
        $this->em->persist($record);
        $this->em->flush();

        return true;
    }

    public function onDisconnect(Entity\Station $station): bool
    {
        foreach ($this->broadcastRepo->getActiveBroadcasts($station) as $broadcast) {
            $broadcast->setTimestampEnd(time());
            $this->em->persist($broadcast);
        }

        $station->setIsStreamerLive(false);
        $station->setCurrentStreamer();
        $this->em->persist($station);
        $this->em->flush();

        return true;
    }

    public function getStreamer(
        Entity\Station $station,
        string $username = '',
        bool $activeOnly = true
    ): ?Entity\StationStreamer {
        $criteria = [
            'station' => $station,
            'streamer_username' => $username,
        ];

        if ($activeOnly) {
            $criteria['is_active'] = 1;
        }

        /** @var Entity\StationStreamer|null $streamer */
        $streamer = $this->repository->findOneBy($criteria);

        return $streamer;
    }

    public function writeArtwork(
        Entity\StationStreamer $streamer,
        string $rawArtworkString
    ): void {
        $artworkPath = Entity\StationStreamer::getArtworkPath($streamer->getIdRequired());
        $artworkString = AlbumArt::resize($rawArtworkString);

        $fsConfig = (new StationFilesystems($streamer->getStation()))->getConfigFilesystem();
        $fsConfig->write($artworkPath, $artworkString);

        $streamer->setArtUpdatedAt(time());
        $this->em->persist($streamer);
    }

    public function removeArtwork(
        Entity\StationStreamer $streamer
    ): void {
        $artworkPath = Entity\StationStreamer::getArtworkPath($streamer->getIdRequired());

        $fsConfig = (new StationFilesystems($streamer->getStation()))->getConfigFilesystem();
        $fsConfig->delete($artworkPath);

        $streamer->setArtUpdatedAt(0);
        $this->em->persist($streamer);
    }

    public function delete(
        Entity\StationStreamer $streamer
    ): void {
        $this->removeArtwork($streamer);

        $this->em->remove($streamer);
        $this->em->flush();
    }
}
