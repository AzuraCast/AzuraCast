<?php

declare(strict_types=1);

namespace App\Service\Meilisearch;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity\Repository\CustomFieldRepository;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Environment;
use App\Service\Meilisearch;
use Meilisearch\Contracts\DocumentsQuery;
use Meilisearch\Endpoints\Indexes;

final class Index
{
    public function __construct(
        private readonly ReloadableEntityManagerInterface $em,
        private readonly CustomFieldRepository $customFieldRepo,
        private readonly Environment $environment,
        private readonly StorageLocation $storageLocation,
        private readonly Indexes $indexClient,
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

        foreach ($this->getStationIds() as $stationId) {
            $filterableAttributes[] = 'station_' . $stationId . '_playlists';
            $filterableAttributes[] = 'station_' . $stationId . '_is_requestable';
            $filterableAttributes[] = 'station_' . $stationId . '_is_on_demand';
        }

        foreach ($this->customFieldRepo->getFieldIds() as $fieldId => $fieldShortCode) {
            $mediaFields[] = 'custom_field_' . $fieldId;
        }

        $indexSettings = [
            'filterableAttributes' => $filterableAttributes,
            'sortableAttributes' => $mediaFields,
            'displayedAttributes' => $this->environment->isProduction()
                ? ['id']
                : ['*'],
        ];

        // Avoid updating settings unless necessary to avoid triggering a reindex.
        $this->indexClient->create(
            $this->indexClient->getUid(),
            ['primaryKey' => 'id']
        );

        $currentSettings = $this->indexClient->getSettings();
        $settingsToUpdate = [];

        foreach ($indexSettings as $settingKey => $setting) {
            $currentSetting = $currentSettings[$settingKey] ?? [];
            if ($currentSetting !== $setting) {
                $settingsToUpdate[$settingKey] = $setting;
            }
        }

        if (!empty($settingsToUpdate)) {
            $this->indexClient->updateSettings($settingsToUpdate);
        }
    }

    public function getIdsInIndex(): iterable
    {
        $perPage = Meilisearch::BATCH_SIZE;
        $documentsQuery = (new DocumentsQuery())
            ->setOffset(0)
            ->setLimit($perPage)
            ->setFields(['id']);

        $documents = $this->indexClient->getDocuments($documentsQuery);
        foreach ($documents->getIterator() as $document) {
            yield $document['id'];
        }

        if ($documents->getTotal() <= $perPage) {
            return;
        }

        $totalPages = ceil($documents->getTotal() / $perPage);
        for ($page = 1; $page <= $totalPages; $page++) {
            $documentsQuery->setOffset($page * $perPage);
            $documents = $this->indexClient->getDocuments($documentsQuery);
            foreach ($documents->getIterator() as $document) {
                yield $document['id'];
            }
        }
    }

    public function deleteIds(array $ids): void
    {
        $this->indexClient->deleteDocuments($ids);
    }

    public function addMedia(array $ids): void
    {
        $this->refreshMedia($ids, true);
    }

    public function refreshMedia(
        array $ids,
        bool $includePlaylists = false
    ): void {
        if ($includePlaylists) {
            $mediaPlaylistsRaw = $this->em->createQuery(
                <<<'DQL'
                    SELECT spm.media_id, spm.playlist_id
                    FROM App\Entity\StationPlaylistMedia spm
                    WHERE spm.media_id IN (:mediaIds)
                DQL
            )->setParameter('mediaIds', $ids)
                ->getArrayResult();

            $mediaPlaylists = [];
            $playlistIds = [];

            foreach ($mediaPlaylistsRaw as $mediaPlaylistRow) {
                $mediaId = $mediaPlaylistRow['media_id'];
                $playlistId = $mediaPlaylistRow['playlist_id'];

                $playlistIds[$playlistId] = $playlistId;

                $mediaPlaylists[$mediaId] ??= [];
                $mediaPlaylists[$mediaId][] = $playlistId;
            }

            $stationIds = $this->getStationIds();

            $playlistsRaw = $this->em->createQuery(
                <<<'DQL'
                SELECT p.id, p.station_id, p.include_in_on_demand, p.include_in_requests
                FROM App\Entity\StationPlaylist p
                WHERE p.id IN (:playlistIds) AND p.station_id IN (:stationIds)
                AND p.is_enabled = 1
                DQL
            )->setParameter('playlistIds', $playlistIds)
                ->setParameter('stationIds', $stationIds)
                ->getArrayResult();

            $playlists = [];
            foreach ($playlistsRaw as $playlistRow) {
                $playlists[$playlistRow['id']] = $playlistRow;
            }
        }

        $customFieldsRaw = $this->em->createQuery(
            <<<'DQL'
            SELECT smcf.media_id, smcf.field_id, smcf.value
            FROM App\Entity\StationMediaCustomField smcf
            WHERE smcf.media_id IN (:mediaIds)
            DQL
        )->setParameter('mediaIds', $ids)
            ->getArrayResult();

        $customFields = [];
        foreach ($customFieldsRaw as $customFieldRow) {
            $mediaId = $customFieldRow['media_id'];

            $customFields[$mediaId] ??= [];
            $customFields[$mediaId]['custom_field_' . $customFieldRow['field_id']] = $customFieldRow['value'];
        }

        $mediaRaw = $this->em->createQuery(
            <<<'DQL'
            SELECT sm.id,
                sm.unique_id,
                sm.path,
                sm.mtime,
                sm.length_text,
                sm.title,
                sm.artist,
                sm.album,
                sm.genre,
                sm.isrc
            FROM App\Entity\StationMedia sm
            WHERE sm.storage_location = :storageLocation
            AND sm.id IN (:ids)
            DQL
        )->setParameter('storageLocation', $this->storageLocation)
            ->setParameter('ids', $ids)
            ->toIterable();

        $media = [];

        foreach ($mediaRaw as $row) {
            $mediaId = $row['id'];

            $record = [
                'id' => $row['unique_id'],
                'path' => $row['path'],
                'mtime' => $row['mtime'],
                'duration' => $row['length_text'],
                'title' => $row['title'],
                'artist' => $row['artist'],
                'album' => $row['album'],
                'genre' => $row['genre'],
                'isrc' => $row['isrc'],
            ];

            if (isset($customFields[$mediaId])) {
                $record = array_merge($record, $customFields[$mediaId]);
            }

            if ($includePlaylists) {
                foreach ($stationIds as $stationId) {
                    $record['station_' . $stationId . '_playlists'] = [];
                    $record['station_' . $stationId . '_is_requestable'] = false;
                    $record['station_' . $stationId . '_is_on_demand'] = false;
                }

                if (isset($mediaPlaylists[$mediaId])) {
                    foreach ($mediaPlaylists[$mediaId] as $mediaPlaylistId) {
                        if (!isset($playlists[$mediaPlaylistId])) {
                            continue;
                        }

                        $playlist = $playlists[$mediaPlaylistId];
                        $stationId = $playlist['station_id'];

                        $record['station_' . $stationId . '_playlists'][] = $mediaPlaylistId;

                        if ($playlist['include_in_requests']) {
                            $record['station_' . $stationId . '_is_requestable'] = true;
                        }
                        if ($playlist['include_in_on_demand']) {
                            $record['station_' . $stationId . '_is_on_demand'] = true;
                        }
                    }
                }
            }

            $media[$mediaId] = $record;
        }

        if ($includePlaylists) {
            $this->indexClient->addDocumentsInBatches(
                $media,
                Meilisearch::BATCH_SIZE
            );
        } else {
            $this->indexClient->updateDocumentsInBatches(
                $media,
                Meilisearch::BATCH_SIZE
            );
        }
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

        $this->indexClient->updateDocumentsInBatches(
            array_values($media),
            Meilisearch::BATCH_SIZE
        );
    }

    /** @return int[] */
    private function getStationIds(): array
    {
        return $this->em->createQuery(
            <<<'DQL'
            SELECT s.id FROM App\Entity\Station s
            WHERE s.media_storage_location = :storageLocation
            AND s.is_enabled = 1
            DQL
        )->setParameter('storageLocation', $this->storageLocation)
            ->getSingleColumnResult();
    }
}
