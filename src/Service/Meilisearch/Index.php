<?php

declare(strict_types=1);

namespace App\Service\Meilisearch;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity\Repository\CustomFieldRepository;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StorageLocation;
use App\Environment;
use App\Service\Meilisearch;
use Doctrine\ORM\AbstractQuery;
use Meilisearch\Contracts\DocumentsQuery;
use Meilisearch\Endpoints\Indexes;
use Meilisearch\Exceptions\ApiException;
use Meilisearch\Search\SearchResult;

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
        try {
            $this->indexClient->fetchRawInfo();
        } catch (ApiException) {
            $response = $this->indexClient->create(
                $this->indexClient->getUid() ?? '',
                ['primaryKey' => 'id']
            );

            $this->indexClient->waitForTask($response['taskUid']);

            $this->indexClient->updatePagination([
                'maxTotalHits' => 100000,
            ]);
        }

        $currentSettings = $this->indexClient->getSettings();
        $settingsToUpdate = [];

        foreach ($indexSettings as $settingKey => $setting) {
            $currentSetting = $currentSettings[$settingKey] ?? [];
            sort($setting);
            if ($currentSetting !== $setting) {
                $settingsToUpdate[$settingKey] = $setting;
            }
        }

        if (!empty($settingsToUpdate)) {
            $response = $this->indexClient->updateSettings($settingsToUpdate);
            $this->indexClient->waitForTask($response['taskUid']);
        }
    }

    public function getIdsInIndex(): array
    {
        $ids = [];
        foreach ($this->getAllDocuments(['id', 'mtime']) as $document) {
            $ids[$document['id']] = $document['mtime'];
        }

        return $ids;
    }

    public function getAllDocuments(array $fields = ['*']): iterable
    {
        $perPage = Meilisearch::BATCH_SIZE;
        $documentsQuery = (new DocumentsQuery())
            ->setOffset(0)
            ->setLimit($perPage)
            ->setFields($fields);

        $documents = $this->indexClient->getDocuments($documentsQuery);
        yield from $documents->getIterator();

        if ($documents->getTotal() <= $perPage) {
            return;
        }

        $totalPages = ceil($documents->getTotal() / $perPage);
        for ($page = 1; $page <= $totalPages; $page++) {
            $documentsQuery->setOffset($page * $perPage);
            $documents = $this->indexClient->getDocuments($documentsQuery);
            yield from $documents->getIterator();
        }
    }

    public function deleteIds(array $ids): void
    {
        $this->indexClient->deleteDocuments($ids);
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
            ->toIterable([], AbstractQuery::HYDRATE_ARRAY);

        $media = [];

        foreach ($mediaRaw as $row) {
            $mediaId = $row['id'];

            $record = [
                'id' => $row['id'],
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

    public function refreshPlaylists(
        Station $station,
        ?array $ids = null
    ): void {
        $stationId = $station->getIdRequired();

        $playlistsKey = 'station_' . $stationId . '_playlists';
        $isRequestableKey = 'station_' . $stationId . '_is_requestable';
        $isOnDemandKey = 'station_' . $stationId . '_is_on_demand';

        $media = [];

        if (null === $ids) {
            $allMediaRaw = $this->em->createQuery(
                <<<'DQL'
                SELECT m.id FROM App\Entity\StationMedia m
                WHERE m.storage_location = :storageLocation
                DQL
            )->setParameter('storageLocation', $this->storageLocation)
                ->toIterable([], AbstractQuery::HYDRATE_ARRAY);

            foreach ($allMediaRaw as $mediaRow) {
                $media[$mediaRow['id']] = [
                    'id' => $mediaRow['id'],
                    $playlistsKey => [],
                    $isRequestableKey => false,
                    $isOnDemandKey => false,
                ];
            }
        } else {
            foreach ($ids as $mediaId) {
                $media[$mediaId] = [
                    'id' => $mediaId,
                    $playlistsKey => [],
                    $isRequestableKey => false,
                    $isOnDemandKey => false,
                ];
            }
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

        if (null === $ids) {
            $mediaInPlaylists = $this->em->createQuery(
                <<<'DQL'
                SELECT spm.media_id, spm.playlist_id
                FROM App\Entity\StationPlaylistMedia spm
                WHERE spm.playlist_id IN (:allPlaylistIds)
                DQL
            )->setParameter('allPlaylistIds', $allPlaylistIds)
                ->toIterable([], AbstractQuery::HYDRATE_ARRAY);
        } else {
            $mediaInPlaylists = $this->em->createQuery(
                <<<'DQL'
                SELECT spm.media_id, spm.playlist_id
                FROM App\Entity\StationPlaylistMedia spm
                WHERE spm.playlist_id IN (:allPlaylistIds)
                AND spm.media_id IN (:mediaIds)
                DQL
            )->setParameter('allPlaylistIds', $allPlaylistIds)
                ->setParameter('mediaIds', $ids)
                ->toIterable([], AbstractQuery::HYDRATE_ARRAY);
        }

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

    /**
     * @return PaginatorAdapter<array>
     */
    public function getSearchPaginator(
        ?string $query,
        array $searchParams = [],
        array $options = [],
    ): PaginatorAdapter {
        return new PaginatorAdapter(
            $this->indexClient,
            $query,
            $searchParams,
            $options,
        );
    }

    public function searchMedia(
        string $query,
        ?StationPlaylist $playlist = null
    ): array {
        $searchParams = [
            'hitsPerPage' => PHP_INT_MAX,
            'page' => 1,
        ];

        if (null !== $playlist) {
            $station = $playlist->getStation();
            $searchParams['filter'] = [
                [
                    'station_' . $station->getIdRequired() . '_playlists = ' . $playlist->getIdRequired(),
                ],
            ];
        }

        /** @var SearchResult $searchResult */
        $searchResult = $this->indexClient->search(
            $query,
            $searchParams
        );

        return array_column($searchResult->getHits(), 'id');
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
