<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Station;
use App\Entity\StationStreamer;
use App\Entity\StationStreamerBroadcast;
use App\Flysystem\StationFilesystems;
use App\Media\AlbumArt;
use App\Utilities\Time;

/**
 * @extends AbstractStationBasedRepository<StationStreamer>
 */
final class StationStreamerRepository extends AbstractStationBasedRepository
{
    protected string $entityClass = StationStreamer::class;

    public function __construct(
        private readonly StationStreamerBroadcastRepository $broadcastRepo
    ) {
    }

    public function onConnect(Station $station, string $username = ''): bool
    {
        // End all current streamer sessions.
        $this->broadcastRepo->endAllActiveBroadcasts($station);

        $streamer = $this->getStreamer($station, $username);
        if (!($streamer instanceof StationStreamer)) {
            return false;
        }

        $station->is_streamer_live = true;
        $station->current_streamer = $streamer;
        $this->em->persist($station);

        $record = new StationStreamerBroadcast($streamer);
        $this->em->persist($record);
        $this->em->flush();

        return true;
    }

    public function onDisconnect(Station $station): bool
    {
        foreach ($this->broadcastRepo->getActiveBroadcasts($station) as $broadcast) {
            $broadcast->timestampEnd = Time::nowUtc();
            $this->em->persist($broadcast);
        }

        $station->is_streamer_live = false;
        $station->current_streamer = null;

        $this->em->persist($station);
        $this->em->flush();

        return true;
    }

    public function getStreamer(
        Station $station,
        string $username = '',
        bool $activeOnly = true
    ): ?StationStreamer {
        $criteria = [
            'station' => $station,
            'streamer_username' => $username,
        ];

        if ($activeOnly) {
            $criteria['is_active'] = 1;
        }

        /** @var StationStreamer|null $streamer */
        $streamer = $this->repository->findOneBy($criteria);

        return $streamer;
    }

    public function writeArtwork(
        StationStreamer $streamer,
        string $rawArtworkString
    ): void {
        $artworkPath = StationStreamer::getArtworkPath($streamer->id);
        $artworkString = AlbumArt::resize($rawArtworkString);

        $fsConfig = StationFilesystems::buildConfigFilesystem($streamer->station);
        $fsConfig->write($artworkPath, $artworkString);

        $streamer->art_updated_at = time();
        $this->em->persist($streamer);
    }

    public function removeArtwork(
        StationStreamer $streamer
    ): void {
        $artworkPath = StationStreamer::getArtworkPath($streamer->id);

        $fsConfig = StationFilesystems::buildConfigFilesystem($streamer->station);
        $fsConfig->delete($artworkPath);

        $streamer->art_updated_at = 0;
        $this->em->persist($streamer);
    }

    public function delete(
        StationStreamer $streamer
    ): void {
        $this->removeArtwork($streamer);

        $this->em->remove($streamer);
        $this->em->flush();
    }
}
