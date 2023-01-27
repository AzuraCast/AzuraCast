<?php

declare(strict_types=1);

namespace App\Service\Meilisearch;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity\Repository\CustomFieldRepository;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Environment;
use Meilisearch\Client;

final class Index
{
    public function __construct(
        private readonly ReloadableEntityManagerInterface $em,
        private readonly CustomFieldRepository $customFieldRepo,
        private readonly Environment $environment,
        private readonly Client $client,
        private readonly StorageLocation $storageLocation,
        private readonly string $indexUid
    ) {
    }

    public function configure(): void
    {
        $filterableAttributes = [];

        $mediaFields = [
            'id',
            'path',
            'mtime',
            'length',
            'title',
            'artist',
            'album',
            'genre',
            'isrc',
        ];

        foreach ($this->iterateStations() as $station) {
            $stationId = $station->getIdRequired();

            $filterableAttributes[] = 'station_' . $stationId . '_playlists';
            $filterableAttributes[] = 'station_' . $stationId . '_is_requestable';
            $filterableAttributes[] = 'station_' . $stationId . '_is_on_demand';
        }

        foreach ($this->customFieldRepo->getFieldIds() as $fieldId => $fieldShortCode) {
            $mediaFields[] = 'custom_field_' . $fieldId;
        }

        $indexSettings = [
            'primaryKey' => 'id',
            'filterableAttributes' => $filterableAttributes,
            'sortableAttributes' => $mediaFields,
            'displayedAttributes' => $this->environment->isProduction()
                ? ['id']
                : ['*'],
        ];

        // Avoid updating settings unless necessary to avoid triggering a reindex.
        $this->client->createIndex($this->indexUid);

        $index = $this->client->index($this->indexUid);
        $currentSettings = $index->getSettings();
        $settingsToUpdate = [];

        foreach ($indexSettings as $settingKey => $setting) {
            $currentSetting = $currentSettings[$settingKey] ?? [];
            if ($currentSetting !== $setting) {
                $settingsToUpdate[$settingKey] = $setting;
            }
        }

        if (!empty($settingsToUpdate)) {
            $index->updateSettings($settingsToUpdate);
        }
    }

    public function addMedia(array $ids): void
    {
        $this->refreshMedia($ids, true);
    }

    public function refreshMedia(
        array $ids,
        bool $includePlaylists = false
    ): void {
    }

    public function refreshPlaylists(Station $station): void
    {
        $stationId = $station->getIdRequired();

        $playlistsKey = 'station_' . $stationId . '_playlists';
        $isRequestableKey = 'station_' . $stationId . '_is_requestable';
        $isOnDemandKey = 'station_' . $stationId . '_is_on_demand';

        $allMediaRaw = $this->em->createQuery(
            <<<'DQL'
            SELECT m.id, m.unique_id FROM App\Entity\StationMedia m
            WHERE m.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $this->storageLocation)
            ->getArrayResult();

        $media = [];
        foreach ($allMediaRaw as $mediaRow) {
            $media[$mediaRow['id']] = [
                'id' => $mediaRow['unique_id'],
                $playlistsKey => [],
                $isRequestableKey => false,
                $isOnDemandKey => false,
            ];
        }

        $allPlaylists = $this->em->createQuery(
            <<<'DQL'
            SELECT p.id, p.include_in_on_demand, p.include_in_requests
            FROM App\Entity\StationPlaylist p
            WHERE p.station = :station AND p.is_enabled = 1
            DQL
        )->setParameter('station', $station)
            ->getArrayResult();

        $allPlaylistIds = [];
        $onDemandPlaylists = [];
        $requestablePlaylists = [];

        foreach ($allPlaylists as $playlist) {
            $allPlaylistIds[] = $playlist['id'];
            if ($playlist['include_in_on_demand']) {
                $onDemandPlaylists[$playlist['id']] = $playlist['id'];
            }
            if ($playlist['include_in_requests']) {
                $requestablePlaylists[$playlist['id']] = $playlist['id'];
            }
        }

        $mediaInPlaylists = $this->em->createQuery(
            <<<'DQL'
            SELECT spm.media_id, spm.playlist_id
            FROM App\Entity\StationPlaylistMedia spm
            WHERE spm.playlist_id IN (:allPlaylistIds)
            DQL
        )->setParameter('allPlaylistIds', $allPlaylistIds)
            ->toIterable();

        foreach ($mediaInPlaylists as $spmRow) {
            $mediaId = $spmRow['media_id'];
            $playlistId = $spmRow['playlist_id'];

            $media[$mediaId][$playlistsKey][] = $playlistId;
            if (isset($requestablePlaylists[$playlistId])) {
                $media[$mediaId][$isRequestableKey] = true;
            }
            if (isset($onDemandPlaylists[$playlistId])) {
                $media[$mediaId][$isOnDemandKey] = true;
            }
        }

        $this->client->index($this->indexUid)->updateDocumentsInBatches(
            array_values($media)
        );
    }

    /** @return Station[] */
    private function iterateStations(): iterable
    {
        return $this->em->createQuery(
            <<<'DQL'
            SELECT s FROM App\Entity\Station s
            WHERE s.media_storage_location = :storageLocation
            AND s.is_enabled = 1
            DQL
        )->setParameter('storageLocation', $this->storageLocation)
            ->toIterable();
    }
}
