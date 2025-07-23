<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\BulkMedia;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\StationPlaylistImportResult;
use App\Entity\Repository\CustomFieldRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationMediaMetadata;
use App\Entity\StationPlaylist;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Flow;
use League\Csv\Reader;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

use function count;
use function str_starts_with;

#[
    OA\Post(
        path: '/station/{station_id}/files/bulk/preview',
        operationId: 'postStationBulkMediaPreview',
        summary: 'Preview changes from a CSV containing details about all station media.',
        tags: [OpenApi::TAG_STATIONS_MEDIA],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class PreviewAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    private const array ALLOWED_MEDIA_FIELDS = [
        'title',
        'artist',
        'album',
        'genre',
        'lyrics',
        'isrc',
    ];

    public function __construct(
        private readonly CustomFieldRepository $customFieldRepo,
        private readonly StationPlaylistRepository $playlistRepo,
        private readonly Serializer $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        // Handle Flow upload.
        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        // Lookup tables for later.
        $mediaStorage = $station->media_storage_location;

        $mediaByPath = [];
        $mediaByUniqueId = [];

        $mediaInStorageLocation = $this->em->createQuery(
            <<<DQL
            SELECT sm.id, sm.unique_id, sm.path, sm.title, sm.artist,
                   sm.album, sm.genre, sm.lyrics, sm.isrc, sm.extra_metadata_raw
            FROM App\Entity\StationMedia sm
            WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $mediaStorage)
            ->getArrayResult();

        $mediaLookup = [];
        foreach ($mediaInStorageLocation as $mediaRow) {
            $mediaByPath[hash('sha256', $mediaRow['path'])] = $mediaRow['id'];
            $mediaByUniqueId[$mediaRow['unique_id']] = $mediaRow['id'];
            $mediaLookup[$mediaRow['id']] = $mediaRow;
        }

        $extraMetadataFieldNames = StationMediaMetadata::getFields();

        $customFieldShortNames = [];
        foreach ($this->customFieldRepo->fetchArray() as $row) {
            $customFieldShortNames[$row['short_name']] = $row['id'];
        }

        $playlistsByName = [];
        foreach ($this->playlistRepo->getAllForStation($station) as $playlist) {
            $shortName = StationPlaylist::generateShortName($playlist->name);
            $playlistsByName[$shortName] = $playlist->id;
        }

        // Read and process CSV.
        $csvPath = $flowResponse->getUploadedPath();

        $reader = Reader::createFromPath($csvPath);
        $reader->setHeaderOffset(0);

        $previewResults = [];
        $totalChanges = 0;

        foreach ($reader->getRecords() as $row) {
            $row = (array)$row;
            if (isset($row['unique_id'], $mediaByUniqueId[$row['unique_id']])) {
                $mediaId = $mediaByUniqueId[$row['unique_id']];
            } elseif (isset($row['path'], $mediaByPath[hash('sha256', $row['path'])])) {
                $mediaId = $mediaByPath[hash('sha256', $row['path'])];
            } else {
                continue;
            }

            if (!isset($mediaLookup[$mediaId])) {
                continue;
            }

            $currentMedia = $mediaLookup[$mediaId];
            unset($row['id'], $row['path']);

            $previewResult = [
                'id' => $mediaId,
                'title' => $currentMedia['title'],
                'artist' => $currentMedia['artist'],
                'has_changes' => false,
                'changes' => [],
                'error' => null,
            ];

            try {
                $changes = $this->previewRow(
                    $currentMedia,
                    $station,
                    $row,
                    $extraMetadataFieldNames,
                    $customFieldShortNames,
                    $playlistsByName
                );

                if (!empty($changes)) {
                    $previewResult['has_changes'] = true;
                    $previewResult['changes'] = $changes;
                    $totalChanges++;
                }
            } catch (Throwable $e) {
                $previewResult['error'] = $e->getMessage();
            }

            $previewResults[] = $previewResult;
        }

        @unlink($csvPath);

        return $response->withJson([
            'success' => true,
            'message' => sprintf(__('%d files will be modified.'), $totalChanges),
            'preview_results' => $previewResults,
            'total_changes' => $totalChanges,
        ]);
    }

    private function previewRow(
        array $currentMedia,
        Station $station,
        array $row,
        array $extraMetadataFieldNames,
        array $customFieldShortNames,
        array $playlistsByName
    ): array {
        $changes = [];
        $mediaChanges = [];
        $extraMetadata = [];

        $hasCustomFields = false;
        $customFields = [];

        $hasPlaylists = false;
        $playlists = [];

        foreach ($row as $key => $value) {
            if ('' === $value) {
                $value = null;
            }

            if (in_array($key, self::ALLOWED_MEDIA_FIELDS, true)) {
                $currentValue = $currentMedia[$key] ?? null;
                if ($currentValue !== $value) {
                    $mediaChanges[$key] = [
                        'field' => $key,
                        'current' => $currentValue,
                        'new' => $value,
                    ];
                }
            } elseif (in_array($key, $extraMetadataFieldNames, true)) {
                $currentExtraMetadata = $currentMedia['extra_metadata_raw'] ?? [];
                $currentValue = $currentExtraMetadata[$key] ?? null;
                if ($currentValue !== $value) {
                    $extraMetadata[$key] = [
                        'field' => $key,
                        'current' => $currentValue,
                        'new' => $value,
                    ];
                }
            } elseif (str_starts_with($key, 'custom_field_')) {
                $fieldName = str_replace('custom_field_', '', $key);
                if (isset($customFieldShortNames[$fieldName])) {
                    $hasCustomFields = true;
                    $customFields[$customFieldShortNames[$fieldName]] = $value;
                }
            } elseif ('playlists' === $key) {
                $hasPlaylists = true;
                if (null !== $value) {
                    foreach (explode(', ', $value) as $playlistName) {
                        $playlistShortName = StationPlaylist::generateShortName($playlistName);
                        if (isset($playlistsByName[$playlistShortName])) {
                            /** @var int $playlistId */
                            $playlistId = $playlistsByName[$playlistShortName];
                            $playlists[$playlistId] = 0;
                        }
                    }
                }
            }
        }

        if (!empty($mediaChanges)) {
            $changes['metadata'] = array_values($mediaChanges);
        }

        if (!empty($extraMetadata)) {
            $changes['extra_metadata'] = array_values($extraMetadata);
        }

        if ($hasPlaylists) {
            // Get current playlists for this media
            $currentPlaylists = $this->em->createQuery(
                <<<DQL
                SELECT sp.id, sp.name
                FROM App\Entity\StationPlaylistMedia spm
                JOIN spm.playlist sp
                WHERE spm.media = :mediaId
                DQL
            )->setParameter('mediaId', $currentMedia['id'])
                ->getArrayResult();

            $currentPlaylistIds = array_column($currentPlaylists, 'id');
            $newPlaylistIds = array_keys($playlists);

            $addedPlaylists = array_diff($newPlaylistIds, $currentPlaylistIds);
            $removedPlaylists = array_diff($currentPlaylistIds, $newPlaylistIds);

            if (!empty($addedPlaylists) || !empty($removedPlaylists)) {
                $playlistChanges = [];

                if (!empty($addedPlaylists)) {
                    $addedPlaylistNames = [];
                    foreach ($playlistsByName as $name => $id) {
                        if (in_array($id, $addedPlaylists, true)) {
                            $addedPlaylistNames[] = $name;
                        }
                    }
                    $playlistChanges['added'] = $addedPlaylistNames;
                }

                if (!empty($removedPlaylists)) {
                    $removedPlaylistNames = [];
                    foreach ($currentPlaylists as $playlist) {
                        if (in_array($playlist['id'], $removedPlaylists, true)) {
                            $removedPlaylistNames[] = $playlist['name'];
                        }
                    }
                    $playlistChanges['removed'] = $removedPlaylistNames;
                }

                $changes['playlists'] = $playlistChanges;
            }
        }

        if ($hasCustomFields) {
            // Get current custom fields for this media
            $currentCustomFields = $this->em->createQuery(
                <<<DQL
                SELECT cf.short_name, smcf.field_value
                FROM App\Entity\StationMediaCustomField smcf
                JOIN smcf.field cf
                WHERE smcf.media = :mediaId
                DQL
            )->setParameter('mediaId', $currentMedia['id'])
                ->getArrayResult();

            $currentCustomFieldValues = [];
            foreach ($currentCustomFields as $field) {
                $currentCustomFieldValues[$field['short_name']] = $field['field_value'];
            }

            $customFieldChanges = [];
            foreach ($customFields as $fieldId => $newValue) {
                $fieldShortName = array_search($fieldId, $customFieldShortNames, true);
                $currentValue = $currentCustomFieldValues[$fieldShortName] ?? null;

                if ($currentValue !== $newValue) {
                    $customFieldChanges[] = [
                        'field' => $fieldShortName,
                        'current' => $currentValue,
                        'new' => $newValue,
                    ];
                }
            }

            if (!empty($customFieldChanges)) {
                $changes['custom_fields'] = $customFieldChanges;
            }
        }

        return $changes;
    }
}
