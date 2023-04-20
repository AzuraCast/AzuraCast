<?php

declare(strict_types=1);

namespace App\Flysystem;

use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Station;
use App\Flysystem\Adapter\LocalAdapterInterface;
use App\Flysystem\Adapter\LocalFilesystemAdapter;

final class StationFilesystems
{
    public function __construct(
        private readonly StorageLocationRepository $storageLocationRepo
    ) {
    }

    public function getMediaFilesystem(Station $station): ExtendedFilesystemInterface
    {
        $mediaAdapter = $this->storageLocationRepo->getAdapter(
            $station->getMediaStorageLocation()
        )->getStorageAdapter();

        return ($mediaAdapter instanceof LocalAdapterInterface)
            ? new LocalFilesystem($mediaAdapter)
            : new RemoteFilesystem($mediaAdapter, $station->getRadioTempDir());
    }

    public function getRecordingsFilesystem(Station $station): ExtendedFilesystemInterface
    {
        $recordingsAdapter = $this->storageLocationRepo->getAdapter(
            $station->getRecordingsStorageLocation()
        )->getStorageAdapter();

        return ($recordingsAdapter instanceof LocalAdapterInterface)
            ? new LocalFilesystem($recordingsAdapter)
            : new RemoteFilesystem($recordingsAdapter, $station->getRadioTempDir());
    }

    public function getPodcastsFilesystem(Station $station): ExtendedFilesystemInterface
    {
        $podcastsAdapter = $this->storageLocationRepo->getAdapter(
            $station->getPodcastsStorageLocation()
        )->getStorageAdapter();

        return ($podcastsAdapter instanceof LocalAdapterInterface)
            ? new LocalFilesystem($podcastsAdapter)
            : new RemoteFilesystem($podcastsAdapter, $station->getRadioTempDir());
    }

    public function getPlaylistsFilesystem(Station $station): LocalFilesystem
    {
        return self::buildPlaylistsFilesystem($station);
    }

    public static function buildPlaylistsFilesystem(
        Station $station
    ): LocalFilesystem {
        return self::buildLocalFilesystemForPath($station->getRadioPlaylistsDir());
    }

    public function getConfigFilesystem(Station $station): LocalFilesystem
    {
        return self::buildConfigFilesystem($station);
    }

    public static function buildConfigFilesystem(
        Station $station
    ): LocalFilesystem {
        return self::buildLocalFilesystemForPath($station->getRadioConfigDir());
    }

    public function getTempFilesystem(Station $station): LocalFilesystem
    {
        return self::buildTempFilesystem($station);
    }

    public static function buildTempFilesystem(
        Station $station
    ): LocalFilesystem {
        return self::buildLocalFilesystemForPath($station->getRadioTempDir());
    }

    public static function buildLocalFilesystemForPath(
        string $path
    ): LocalFilesystem {
        return new LocalFilesystem(new LocalFilesystemAdapter($path));
    }
}
