<?php

declare(strict_types=1);

namespace App\Service\Meilisearch;

use App\Entity\Repository\StationRepository;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Message\AbstractMessage;
use App\Message\Meilisearch\AddMediaMessage;
use App\Message\Meilisearch\UpdatePlaylistsMessage;
use App\Service\Meilisearch;

final class MessageHandler
{
    public function __construct(
        private readonly Meilisearch $meilisearch,
        private readonly StorageLocationRepository $storageLocationRepo,
        private readonly StationRepository $stationRepo
    ) {
    }

    public function __invoke(AbstractMessage $message): void
    {
        if (!$this->meilisearch->isSupported()) {
            return;
        }

        match (true) {
            $message instanceof AddMediaMessage => $this->addMedia($message),
            $message instanceof UpdatePlaylistsMessage => $this->updatePlaylists($message),
            default => null,
        };
    }

    private function addMedia(AddMediaMessage $message): void
    {
        $storageLocation = $this->storageLocationRepo->find($message->storage_location_id);
        if (!($storageLocation instanceof StorageLocation)) {
            return;
        }

        $index = $this->meilisearch->getIndex($storageLocation);

        $index->refreshMedia(
            $message->media_ids,
            $message->include_playlists
        );
    }

    private function updatePlaylists(UpdatePlaylistsMessage $message): void
    {
        $station = $this->stationRepo->find($message->station_id);
        if (!($station instanceof Station)) {
            return;
        }

        $storageLocation = $station->getMediaStorageLocation();

        $index = $this->meilisearch->getIndex($storageLocation);
        $index->refreshPlaylists($station, $message->media_ids);
    }
}
