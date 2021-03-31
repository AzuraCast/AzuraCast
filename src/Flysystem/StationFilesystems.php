<?php

namespace App\Flysystem;

use App\Entity;
use App\Flysystem\Adapter\LocalAdapter;

class StationFilesystems
{
    protected Entity\Station $station;

    protected FilesystemInterface $fsMedia;

    protected FilesystemInterface $fsRecordings;

    protected LocalFilesystem $fsPlaylists;

    protected LocalFilesystem $fsConfig;

    protected LocalFilesystem $fsTemp;

    public function __construct(Entity\Station $station)
    {
        $this->station = $station;
    }

    public function getMediaFilesystem(): FilesystemInterface
    {
        if (!isset($this->fsMedia)) {
            $mediaAdapter = $this->station->getMediaStorageLocation()->getStorageAdapter();
            if ($mediaAdapter instanceof LocalAdapter) {
                $this->fsMedia = new LocalFilesystem($mediaAdapter);
            } else {
                $tempDir = $this->station->getRadioTempDir();
                $this->fsMedia = new RemoteFilesystem($mediaAdapter, $tempDir);
            }
        }

        return $this->fsMedia;
    }

    public function getRecordingsFilesystem(): FilesystemInterface
    {
        if (!isset($this->fsRecordings)) {
            $recordingsAdapter = $this->station->getRecordingsStorageLocation()->getStorageAdapter();
            if ($recordingsAdapter instanceof LocalAdapter) {
                $this->fsRecordings = new LocalFilesystem($recordingsAdapter);
            } else {
                $tempDir = $this->station->getRadioTempDir();
                $this->fsRecordings = new RemoteFilesystem($recordingsAdapter, $tempDir);
            }
        }

        return $this->fsRecordings;
    }

    public function getPlaylistsFilesystem(): LocalFilesystem
    {
        if (!isset($this->fsPlaylists)) {
            $playlistsDir = $this->station->getRadioPlaylistsDir();
            $this->fsPlaylists = new LocalFilesystem(new LocalAdapter($playlistsDir));
        }

        return $this->fsPlaylists;
    }

    public function getConfigFilesystem(): LocalFilesystem
    {
        if (!isset($this->fsConfig)) {
            $configDir = $this->station->getRadioConfigDir();
            $this->fsConfig = new LocalFilesystem(new LocalAdapter($configDir));
        }

        return $this->fsConfig;
    }

    public function getTempFilesystem(): LocalFilesystem
    {
        if (!isset($this->fsTemp)) {
            $tempDir = $this->station->getRadioTempDir();
            $this->fsTemp = new LocalFilesystem(new LocalAdapter($tempDir));
        }

        return $this->fsTemp;
    }
}
