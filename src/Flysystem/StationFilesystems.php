<?php

declare(strict_types=1);

namespace App\Flysystem;

use App\Entity;
use Azura\Files\Adapter\Local\LocalFilesystemAdapter;
use Azura\Files\Adapter\LocalAdapterInterface;
use Azura\Files\ExtendedFilesystemInterface;
use Azura\Files\LocalFilesystem;
use Azura\Files\RemoteFilesystem;

class StationFilesystems
{
    protected ExtendedFilesystemInterface $fsMedia;

    protected ExtendedFilesystemInterface $fsRecordings;

    protected ExtendedFilesystemInterface $fsPodcasts;

    protected LocalFilesystem $fsPlaylists;

    protected LocalFilesystem $fsConfig;

    protected LocalFilesystem $fsTemp;

    public function __construct(
        protected Entity\Station $station
    ) {
    }

    public function getMediaFilesystem(): ExtendedFilesystemInterface
    {
        if (!isset($this->fsMedia)) {
            $mediaAdapter = $this->station->getMediaStorageLocation()->getStorageAdapter();
            if ($mediaAdapter instanceof LocalAdapterInterface) {
                $this->fsMedia = new LocalFilesystem($mediaAdapter);
            } else {
                $tempDir = $this->station->getRadioTempDir();
                $this->fsMedia = new RemoteFilesystem($mediaAdapter, $tempDir);
            }
        }

        return $this->fsMedia;
    }

    public function getRecordingsFilesystem(): ExtendedFilesystemInterface
    {
        if (!isset($this->fsRecordings)) {
            $recordingsAdapter = $this->station->getRecordingsStorageLocation()->getStorageAdapter();
            if ($recordingsAdapter instanceof LocalAdapterInterface) {
                $this->fsRecordings = new LocalFilesystem($recordingsAdapter);
            } else {
                $tempDir = $this->station->getRadioTempDir();
                $this->fsRecordings = new RemoteFilesystem($recordingsAdapter, $tempDir);
            }
        }

        return $this->fsRecordings;
    }

    public function getPodcastsFilesystem(): ExtendedFilesystemInterface
    {
        if (!isset($this->fsPodcasts)) {
            $podcastsAdapter = $this->station->getPodcastsStorageLocation()->getStorageAdapter();
            if ($podcastsAdapter instanceof LocalAdapterInterface) {
                $this->fsPodcasts = new LocalFilesystem($podcastsAdapter);
            } else {
                $tempDir = $this->station->getRadioTempDir();
                $this->fsPodcasts = new RemoteFilesystem($podcastsAdapter, $tempDir);
            }
        }

        return $this->fsPodcasts;
    }

    public function getPlaylistsFilesystem(): LocalFilesystem
    {
        if (!isset($this->fsPlaylists)) {
            $playlistsDir = $this->station->getRadioPlaylistsDir();
            $this->fsPlaylists = new LocalFilesystem(new LocalFilesystemAdapter($playlistsDir));
        }

        return $this->fsPlaylists;
    }

    public function getConfigFilesystem(): LocalFilesystem
    {
        if (!isset($this->fsConfig)) {
            $configDir = $this->station->getRadioConfigDir();
            $this->fsConfig = new LocalFilesystem(new LocalFilesystemAdapter($configDir));
        }

        return $this->fsConfig;
    }

    public function getTempFilesystem(): LocalFilesystem
    {
        if (!isset($this->fsTemp)) {
            $tempDir = $this->station->getRadioTempDir();
            $this->fsTemp = new LocalFilesystem(new LocalFilesystemAdapter($tempDir));
        }

        return $this->fsTemp;
    }
}
